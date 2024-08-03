<?php

namespace StonFi\contracts\dex\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use StonFi\const\v1\gas\provide\LpJettonGas;
use StonFi\const\v1\gas\provide\LpTonGas;
use StonFi\const\v1\models\TransactionParams;
use StonFi\const\OpCodes;
use StonFi\contracts\api\v1\Jetton;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\common\CreateJettonTransferMessage;
use StonFi\Init;
use StonFi\pTON\v1\PtonV1;

class ProvideLiquidity
{
    private Address $routerAddress;
    private Init $init;
    private CallContractMethods $CallContractMethods;

    public function __construct(Init $init, $contractAddress = null, $CallContractMethods = null)
    {
        $this->init = $init;
        if ($contractAddress == null) {
            $this->routerAddress = $init->getRouter();
        } else {
            $this->routerAddress = $contractAddress;
        }

        if (isset($CallContractMethods))
            $this->CallContractMethods = $CallContractMethods;
        else
            $this->CallContractMethods = new CallContractMethods($this->init);
    }

    /**
     * @throws \Olifanton\Interop\Boc\Exceptions\CellException
     * @throws \Brick\Math\Exception\DivisionByZeroException
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     */
    public function JettonTxParams(
        Address    $userWalletAddress,
        Address    $sendTokenAddress,
        Address    $otherTokenAddress,
        BigInteger $sendAmount,
        BigInteger $minLpOut,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    ): TransactionParams
    {
        $jettonWalletAddress = $this->CallContractMethods->getWalletAddress($userWalletAddress->toString(), $sendTokenAddress->toString());
        $routerWalletAddress = $this->CallContractMethods->getWalletAddress($this->routerAddress->toString(), $otherTokenAddress->toString());

        $forwardPayload = $this->createProvideLiquidityBody(
            routerWalletAddress: $routerWalletAddress,
            minLpOut: $minLpOut
        );

        $forwardTonAmount = $forwardGasAmount ?? (new LpJettonGas())->forwardGasAmount;

        $body = CreateJettonTransferMessage::create(
            queryId: $queryId ?? 0,
            amount:$sendAmount,
            destination: $this->routerAddress,
            forwardTonAmount: $forwardTonAmount,
            forwardPayload: $forwardPayload,
            responseDestination: $userWalletAddress
        );

        $amount = $gasAmount ?? (new LpJettonGas())->gasAmount;

        return new TransactionParams($jettonWalletAddress, Bytes::bytesToBase64($body->toBoc(false)), $amount);
    }

    /**
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     */
    public function TonTxParams(
        Address    $userWalletAddress,
        PtonV1     $proxyTon,
        Address    $otherTokenAddress,
        BigInteger $sendAmount,
        BigInteger $minLpOut,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    )
    {
        $routerWalletAddress = $this->CallContractMethods->getWalletAddress($this->routerAddress->toString(), $otherTokenAddress->toString());

        $forwardPayload = $this->createProvideLiquidityBody(
            routerWalletAddress: $routerWalletAddress,
            minLpOut: $minLpOut
        );

        $forwardTonAmount = $forwardGasAmount ?? (new LpTonGas())->forwardGasAmount;

        return $proxyTon->getTonTransferTxParams(
            $this->routerAddress,
            $sendAmount,
            $this->routerAddress,
            $userWalletAddress,
            $forwardPayload,
            $forwardTonAmount,
            $queryId
        );
    }

    /**
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     */
    public function createProvideLiquidityBody(
        Address    $routerWalletAddress,
        BigInteger $minLpOut
    ): \Olifanton\Interop\Boc\Cell
    {
        $builder = new Builder();
        return $builder
            ->writeUint(OpCodes::PROVIDE_LP, 32)
            ->writeAddress($routerWalletAddress)
            ->writeCoins($minLpOut)
            ->cell();
    }

}