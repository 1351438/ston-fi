<?php
declare(strict_types=1);

namespace StonFi\dex\swap\v1;

use Brick\Math\BigInteger;
use JetBrains\PhpStorm\ArrayShape;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Bytes;
use StonFi\const\gas\swap\JettonToJettonGas;
use StonFi\const\gas\swap\JettonToTonGas;
use StonFi\const\gas\swap\TonToJettonGas;
use StonFi\const\OpCodes;
use StonFi\dex\api\v1\Jetton;
use StonFi\dex\common\CreateJettonTransferMessage;
use StonFi\Init;
use StonFi\pTON\v1\PtonV1;

class Swap
{
    private Init $init;

    public function __construct(Init $init)
    {
        $this->init = $init;
    }

    public function simulate($from, $to, $units, $slippageTolerance): bool|string
    {
        $swap = new \StonFi\dex\api\v1\Swap($this->init);
        return $swap->swapSimulate($from, $to, $units, $slippageTolerance);
    }

    /**
     * Contract address is must be The Ston Fi Router address
     * userWalletAddress is the user wallet address who want to make the swap
     * offerJettonAddress the first currency user want to send
     * askJettonAddress is the second currency that user want to receive
     * BigInteger $offerAmount of money user want to send for trade
     * BigInteger $minAskAmount the minimum asked of second currency
     * @throws CellException
     * @throws BitStringException
     */
    #[ArrayShape(["address" => "string", "payload" => "string", "amount" => "mixed"])]
    public function JettonToJettonTxParams(
        Address $contractAddress,
        Address $userWalletAddress,
        Address $offerJettonAddress,
        Address $askJettonAddress,
                $offerAmount,
                $minAskAmount,
        Address $referralAddress = null,
                $gasAmount = null,
                $forwardGasAmount = null,
                $queryId = null
    ): array
    {
        $jetton = new Jetton($this->init);
        $offerJettonWalletAddress = json_decode($jetton->jettonWalletAddress($userWalletAddress->toString(), $offerJettonAddress->toString()), true)['address'];
        $askJettonWalletAddress = json_decode($jetton->jettonWalletAddress($contractAddress->toString(), $askJettonAddress->toString()), true)['address'];
        if (Address::isValid($offerJettonWalletAddress) && Address::isValid($askJettonWalletAddress)) {
            $offerJettonWalletAddress = new Address($offerJettonWalletAddress);
            $askJettonWalletAddress = new Address($askJettonWalletAddress);
        } else {
            throw new \Exception("Invalid jetton wallet address");
        }
        $forwardTonAmount = ($forwardGasAmount ?? (new JettonToJettonGas())->forwardGasAmount);
        $forwardPayload = $this->createSwapBody(
            userWalletAddress: $userWalletAddress,
            askJettonWalletAddress: $askJettonWalletAddress,
            minAskAmount: $minAskAmount,
            referralAddress: $referralAddress,
        );

        $body = CreateJettonTransferMessage::create(
            queryId: $queryId,
            amount: $offerAmount,
            destination: $contractAddress,
            forwardTonAmount: $forwardTonAmount,
            forwardPayload: $forwardPayload,
            responseDestination: $userWalletAddress
        );

        $value = $gasAmount ?? (new JettonToJettonGas())->gasAmount;

        return [
            "address" => $offerJettonWalletAddress->toString(),
            "payload" => Bytes::bytesToBase64($body->toBoc()),
            "amount" => $value
        ];
    }

    public
    function JettonToTonTxParams(
        Address    $contractAddress,
        PtonV1     $proxyTon,
        Address    $userWalletAddress,
        Address    $offerJettonAddress,
        BigInteger $offerAmount,
        BigInteger $minAskAmount,
        Address    $referralAddress = null,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    )
    {
        return $this->JettonToJettonTxParams(
            contractAddress: $contractAddress,
            userWalletAddress: $userWalletAddress,
            offerJettonAddress: $offerJettonAddress,
            askJettonAddress: $proxyTon->address,
            offerAmount: $offerAmount,
            minAskAmount: $minAskAmount,
            referralAddress: $referralAddress,
            gasAmount: $gasAmount ?? (new JettonToTonGas())->gasAmount,
            forwardGasAmount: $forwardGasAmount ?? (new JettonToTonGas())->forwardGasAmount,
            queryId: $queryId ?? 0
        );
    }

    /**
     * @throws BitStringException
     * @throws \Exception
     */
    #[ArrayShape(["address" => "string", "payload" => "string", "value" => "\Brick\Math\BigInteger"])] public
    function TonToJettonTxParams(
        Address    $contractAddress,
        PtonV1     $proxyTon,
        Address    $userWalletAddress,
        Address    $askJettonAddress,
        BigInteger $offerAmount,
        BigInteger $minAskAmount,
        Address    $referralAddress = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    ): array
    {
        $jetton = new Jetton($this->init);
        $askJettonWalletAddress = json_decode($jetton->jettonWalletAddress($contractAddress->toString(), $askJettonAddress->toString()), true)['address'];
        if (Address::isValid($askJettonWalletAddress)) {
            $askJettonWalletAddress = new Address($askJettonWalletAddress);
        } else {
            throw new \Exception("Invalid jetton wallet address");
        }

        $forwardPayload = $this->createSwapBody(
            userWalletAddress: $userWalletAddress,
            askJettonWalletAddress: $askJettonWalletAddress,
            minAskAmount: $minAskAmount,
            referralAddress: $referralAddress,
        );

        $forwardTonAmount = $forwardGasAmount ?? (new TonToJettonGas())->forwardGasAmount;

        return $proxyTon->getTonTransferTxParams(
            contractAddress: $contractAddress,
            tonAmount: $offerAmount,
            destinationAddress: $contractAddress,
            refundAddress: $userWalletAddress,
            forwardPayload: $forwardPayload,
            forwardTonAmount: $forwardTonAmount,
            queryId: $queryId ?? 0
        );
    }

    /**
     * @throws BitStringException
     */
    public
    function createSwapBody(
        Address $userWalletAddress,
        Address $askJettonWalletAddress,
                $minAskAmount,
        Address $referralAddress = null
    ): Cell
    {
        $c = new Builder();

        $c
            ->writeUint(OpCodes::SWAP, 32)
            ->writeAddress($askJettonWalletAddress)
            ->writeCoins($minAskAmount)
            ->writeAddress($userWalletAddress);

        if ($referralAddress != null) {
            $c->writeUint(1, 1);
            $c->writeAddress($referralAddress);
        } else {
            $c->writeUint(0, 1);
        }
        return $c->cell();
    }

}