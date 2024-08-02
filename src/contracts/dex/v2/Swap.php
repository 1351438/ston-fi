<?php
declare(strict_types=1);

namespace StonFi\contracts\dex\v2;

use Brick\Math\BigInteger;
use JetBrains\PhpStorm\ArrayShape;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use Olifanton\Ton\Connect\Request\Transaction;
use StonFi\const\v1\gas\swap\JettonToJettonGas;
use StonFi\const\v1\gas\swap\JettonToTonGas;
use StonFi\const\v1\gas\swap\TonToJettonGas;
use StonFi\const\v1\models\TransactionParams;
use StonFi\const\OpCodes;
use StonFi\contracts\api\v1\Jetton;
use StonFi\contracts\common\CreateJettonTransferMessage;
use StonFi\Init;
use StonFi\pTON\v2\PtonV2;

class Swap
{
    private Init $init;
    private Address $routerAddress;

    public function __construct(Init $init, $contractAddress = null)
    {
        $this->init = $init;
        if ($contractAddress == null) {
            $this->routerAddress = $init->getRouter();
        } else {
            $this->routerAddress = $contractAddress;
        }
    }

    public function simulate($from, $to, $units, $slippageTolerance)
    {
        $swap = new \StonFi\contracts\api\v1\Swap($this->init);
        return $swap->swapSimulate($from, $to, $units, $slippageTolerance);
    }

    /**
     * @throws \Olifanton\Interop\Boc\Exceptions\CellException
     * @throws \Brick\Math\Exception\DivisionByZeroException
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     */
    public function JettonToJettonTxParams(
        Address $userWalletAddress,
        Address $offerJettonAddress,
        Address $askJettonAddress,
                $offerAmount,
                $minAskAmount,
        Address $refundAddress = null,
        Address $excessesAddress = null,
        Address $referralAddress = null,
                $referralValue = null,
        Cell    $customPayload = null,
                $customPayloadForwardGasAmount = null,
        Cell    $refundPayload = null,
                $refundForwardGasAmount = null,
                $gasAmount = null,
                $forwardGasAmount = null,
                $queryId = null
    ): TransactionParams
    {
        $jetton = new Jetton($this->init);
        $offerJettonWalletAddress = json_decode($jetton->jettonWalletAddress($userWalletAddress->toString(), $offerJettonAddress->toString()), true)['address'];
        $askJettonWalletAddress = json_decode($jetton->jettonWalletAddress($this->routerAddress->toString(), $askJettonAddress->toString()), true)['address'];
        if (Address::isValid($offerJettonWalletAddress) && Address::isValid($askJettonWalletAddress)) {
            $offerJettonWalletAddress = new Address($offerJettonWalletAddress);
            $askJettonWalletAddress = new Address($askJettonWalletAddress);
        } else {
            throw new \Exception("Invalid jetton wallet address");
        }
        $forwardTonAmount = ($forwardGasAmount ?? (new JettonToJettonGas())->forwardGasAmount);
        $forwardPayload = $this->createSwapBody(
            askJettonWalletAddress: $askJettonWalletAddress,
            receiverAddress: $userWalletAddress,
            refundAddress: $refundAddress ?? $userWalletAddress,
            minAskAmount: $minAskAmount,
            excessesAddress: $excessesAddress,
            customPayload: $customPayload,
            customPayloadForwardGasAmount: $customPayloadForwardGasAmount,
            refundPayload: $refundPayload,
            refundForwardGasAmount: $refundForwardGasAmount,
            referralAddress: $referralAddress,
            referralValue: $referralValue
        );

        $body = CreateJettonTransferMessage::create(
            queryId: $queryId,
            amount: $offerAmount,
            destination: $this->routerAddress,
            forwardTonAmount: $forwardTonAmount,
            forwardPayload: $forwardPayload,
            responseDestination: $userWalletAddress
        );

        $value = $gasAmount ?? (new JettonToJettonGas())->gasAmount;


        return new TransactionParams($offerJettonWalletAddress->toString(), Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    public
    function JettonToTonTxParams(
        PtonV2 $proxyTon,
        Address $userWalletAddress,
        Address $offerJettonAddress,
        BigInteger $offerAmount,
        BigInteger $minAskAmount,
        Address $refundAddress = null,
        Address $excessAddress = null,
        Address $referralAddress = null,
        BigInteger $referralValue = null,
        Cell $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        Cell $refundPayload = null,
        BigInteger $refundForwardGasAmount = null,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
        $queryId = null
    )
    {
        return $this->JettonToJettonTxParams(
            userWalletAddress: $userWalletAddress,
            offerJettonAddress: $offerJettonAddress,
            askJettonAddress: $proxyTon->address,
            offerAmount: $offerAmount,
            minAskAmount: $minAskAmount,
            refundAddress: $refundAddress,
            excessesAddress: $excessAddress,
            referralAddress: $referralAddress,
            referralValue: $referralValue,
            customPayload: $customPayload,
            customPayloadForwardGasAmount: $customPayloadForwardGasAmount,
            refundPayload: $refundPayload,
            refundForwardGasAmount: $refundForwardGasAmount,
            gasAmount: $gasAmount ?? (new JettonToTonGas())->gasAmount,
            forwardGasAmount: $forwardGasAmount ?? (new JettonToTonGas())->forwardGasAmount,
            queryId: $queryId
        );
    }

    #[ArrayShape(["address" => "string", "payload" => "string", "value" => "\Brick\Math\BigInteger"])] public
    function TonToJettonTxParams(
        PtonV2 $proxyTon,
        Address $userWalletAddress,
        Address $askJettonAddress,
        BigInteger $offerAmount,
        BigInteger $minAskAmount,
        Address $refundAddress = null,
        Address $excessAddress = null,
        Address $referralAddress = null,
        BigInteger $referralValue = null,
        Cell $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        Cell $refundPayload = null,
        BigInteger $refundForwardGasAmount = null,
        BigInteger $forwardGasAmount = null,
        $queryId = null
    ): array
    {
        $jetton = new Jetton($this->init);
        $askJettonWalletAddress = json_decode($jetton->jettonWalletAddress($this->routerAddress->toString(), $askJettonAddress->toString()), true)['address'];
        if (Address::isValid($askJettonWalletAddress)) {
            $askJettonWalletAddress = new Address($askJettonWalletAddress);
        } else {
            throw new \Exception("Invalid jetton wallet address");
        }

        $forwardPayload = $this->createSwapBody(
            askJettonWalletAddress: $askJettonWalletAddress,
            receiverAddress: $userWalletAddress,
            refundAddress: $refundAddress,
            minAskAmount: $minAskAmount,
            excessesAddress: $excessAddress,
            customPayload: $customPayload,
            customPayloadForwardGasAmount: $customPayloadForwardGasAmount,
            refundPayload: $refundPayload,
            refundForwardGasAmount: $refundForwardGasAmount,
            referralAddress: $referralAddress,
            referralValue: $referralValue
        );

        $forwardTonAmount = $forwardGasAmount ?? (new TonToJettonGas())->forwardGasAmount;

        return $proxyTon->getTonTransferTxParams(
            contractAddress: $this->routerAddress,
            tonAmount: $offerAmount,
            destinationAddress: $this->routerAddress,
            refundAddress: $userWalletAddress,
            forwardPayload: $forwardPayload,
            forwardTonAmount: $forwardTonAmount,
            queryId: $queryId
        );
    }

    /**
     * @throws \Brick\Math\Exception\DivisionByZeroException
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     */
    public
    function createSwapBody(
        Address $askJettonWalletAddress,
        Address $receiverAddress,
        Address $refundAddress,
                $minAskAmount,
        Address $excessesAddress = null,
        Cell    $customPayload = null,
                $customPayloadForwardGasAmount = null,
                $refundPayload = null,
                $refundForwardGasAmount = null,
        Address $referralAddress = null,
                $referralValue = null
    )
    {
        $c = new Builder();

        $swapData = new Builder();
        $swapData
            ->writeCoins($minAskAmount)
            ->writeAddress($receiverAddress)
            ->writeCoins($customPayloadForwardGasAmount ?? 0)
            ->writeMaybeRef($customPayload)
            ->writeCoins($refundForwardGasAmount ?? 0)
            ->writeMaybeRef($refundPayload)
            ->writeUint($referralValue ?? BigInteger::of(10), 16)
            ->writeAddress($referralAddress ?? null);

        $c
            ->writeUint(OpCodes::SWAP, 32)
            ->writeAddress($askJettonWalletAddress)
            ->writeAddress($refundAddress)
            ->writeAddress($excessesAddress ?? $refundAddress)
            ->writeRef($swapData->cell());

        return $c->cell();
    }
}