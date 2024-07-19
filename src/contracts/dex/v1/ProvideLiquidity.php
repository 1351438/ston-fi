<?php

namespace StonFi\contracts\dex\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Bytes;
use StonFi\const\gas\provide\LpJettonGas;
use StonFi\const\gas\provide\LpTonGas;
use StonFi\const\OpCodes;
use StonFi\contracts\api\v1\Jetton;
use StonFi\contracts\common\CreateJettonTransferMessage;
use StonFi\Init;
use StonFi\pTON\v1\PtonV1;

class ProvideLiquidity
{
    private Address $routerAddress;
    private Init $init;

    public function __construct(Init $init, $contractAddress = null)
    {
        $this->init = $init;
        if ($contractAddress == null) {
            $this->routerAddress = $init->getRouter();
        } else {
            $this->routerAddress = $contractAddress;
        }
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
    )
    {
        $jetton = new Jetton($this->init);
        $jettonWalletAddress = json_decode($jetton->jettonWalletAddress($userWalletAddress->toString(), $sendTokenAddress->toString()), true)['address'];
        $routerWalletAddress = json_decode($jetton->jettonWalletAddress($this->routerAddress->toString(), $otherTokenAddress->toString()), true)['address'];
        if (Address::isValid($jettonWalletAddress) && Address::isValid($routerWalletAddress)) {
            $jettonWalletAddress = new Address($jettonWalletAddress);
            $routerWalletAddress = new Address($routerWalletAddress);
        } else {
            throw new \Exception("Invalid jetton wallet address");
        }

        $forwardPayload = $this->createProvideLiquidityBody(
            routerWalletAddress: $routerWalletAddress,
            minLpOut: $minLpOut
        );

        $forwardTonAmount = $forwardGasAmount ?? (new LpJettonGas())->forwardGasAmount;
        $amount = $gasAmount ?? (new LpJettonGas())->gasAmount;
        $body = CreateJettonTransferMessage::create(
            $queryId ?? BigInteger::of(0),
            $sendAmount,
            $this->routerAddress,
            $forwardTonAmount,
            $forwardPayload,
            responseDestination: $userWalletAddress
        );

        return [
            "address" => $jettonWalletAddress,
            "payload" => Bytes::bytesToBase64($body->toBoc()),
            "amount" => $amount
        ];
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
        $jetton = new Jetton($this->init);
        $routerWalletAddress = json_decode($jetton->jettonWalletAddress($this->routerAddress->toString(), $otherTokenAddress->toString()), true)['address'];
        if (Address::isValid($routerWalletAddress)) {
            $routerWalletAddress = new Address($routerWalletAddress);
        } else {
            throw new \Exception("Invalid jetton wallet address");
        }

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
            $forwardTonAmount
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