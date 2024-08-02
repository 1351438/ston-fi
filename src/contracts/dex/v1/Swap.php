<?php
declare(strict_types=1);

namespace StonFi\contracts\dex\v1;

use Brick\Math\BigInteger;
use JetBrains\PhpStorm\ArrayShape;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Bytes;
use Olifanton\Ton\Connect\Request\Transaction;
use StonFi\const\v1\gas\swap\JettonToJettonGas;
use StonFi\const\v1\gas\swap\JettonToTonGas;
use StonFi\const\v1\gas\swap\TonToJettonGas;
use StonFi\const\v1\models\TransactionParams;
use StonFi\const\OpCodes;
use StonFi\contracts\api\v1\Jetton;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\common\CreateJettonTransferMessage;
use StonFi\Init;
use StonFi\pTON\v1\PtonV1;

class Swap
{
    private Init $init;
    private Address $routerAddress;
    private CallContractMethods $CallContractMethod;

    /**
     * @return mixed|Address
     */
    public function getRouterAddress(): mixed
    {
        return $this->routerAddress;
    }

    public function __construct(Init $init, $contractAddress = null, $callContractMethod = null)
    {
        $this->init = $init;
        if ($contractAddress == null) {
            $this->routerAddress = $init->getRouter();
        } else {
            $this->routerAddress = $contractAddress;
        }

        if ($callContractMethod)
            $this->CallContractMethod = $callContractMethod;
        else
            $this->CallContractMethod = new CallContractMethods($this->init);
    }

    public function simulate($from, $to, $units, $slippageTolerance): bool|string
    {
        $swap = new \StonFi\contracts\api\v1\Swap($this->init);
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
     * @throws \Olifanton\Interop\Boc\Exceptions\SliceException
     */
    public function JettonToJettonTxParams(
        Address    $userWalletAddress,
        Address    $offerJettonAddress,
        Address    $askJettonAddress,
        BigInteger $offerAmount,
        BigInteger $minAskAmount,
        Address    $referralAddress = null,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    ): TransactionParams
    {
        try {

            $offerJettonWalletAddress = $this->CallContractMethod->getWalletAddress($userWalletAddress->toString(), $offerJettonAddress->toString());
            $askJettonWalletAddress = $this->CallContractMethod->getWalletAddress($this->routerAddress->toString(), $askJettonAddress->toString());

        } catch (\Exception $e) {
            throw $e;
        }
        $forwardTonAmount = ($forwardGasAmount ?? (new JettonToJettonGas())->forwardGasAmount);
        $forwardPayload = $this->createSwapBody(
            userWalletAddress: $userWalletAddress,
            askJettonWalletAddress: $askJettonWalletAddress,
            minAskAmount: $minAskAmount,
            referralAddress: $referralAddress,
        );

        $body = CreateJettonTransferMessage::create(
            queryId: $queryId ?? 0,
            amount: $offerAmount,
            destination: $this->routerAddress,
            forwardTonAmount: $forwardTonAmount,
            forwardPayload: $forwardPayload,
            responseDestination: $userWalletAddress
        );

        $value = $gasAmount ?? (new JettonToJettonGas())->gasAmount;

        return new TransactionParams($offerJettonWalletAddress, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    public function JettonToTonTxParams(
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
    public function TonToJettonTxParams(
        PtonV1     $proxyTon,
        Address    $userWalletAddress,
        Address    $askJettonAddress,
        BigInteger $offerAmount,
        BigInteger $minAskAmount,
        Address    $referralAddress = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    ): TransactionParams
    {
        $askJettonWalletAddress = $this->CallContractMethod->getWalletAddress($this->routerAddress->toString(), $askJettonAddress->toString());


        $forwardPayload = $this->createSwapBody(
            userWalletAddress: $userWalletAddress,
            askJettonWalletAddress: $askJettonWalletAddress,
            minAskAmount: $minAskAmount,
            referralAddress: $referralAddress,
        );

        $forwardTonAmount = $forwardGasAmount ?? (new TonToJettonGas())->forwardGasAmount;

        return $proxyTon->getTonTransferTxParams(
            contractAddress: $this->routerAddress,
            tonAmount: $offerAmount,
            destinationAddress: $this->routerAddress,
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