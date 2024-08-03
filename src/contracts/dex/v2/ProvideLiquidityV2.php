<?php

namespace StonFi\contracts\dex\v2;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Boc\Exceptions\SliceException;
use Olifanton\Interop\Bytes;
use StonFi\const\OpCodes;
use StonFi\const\v1\models\TransactionParams;
use StonFi\const\v2\gas\provide\LpJettonGas;
use StonFi\const\v2\gas\provide\LpTonGas;
use StonFi\const\v2\gas\provide\SingleSideLpJettonGas;
use StonFi\const\v2\gas\provide\SingleSideLpTonGas;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\common\CreateJettonTransferMessage;
use StonFi\Init;
use StonFi\pTON\pTON;

class ProvideLiquidityV2
{
    private Address $routerAddress;
    private Init $init;
    private CallContractMethods $provider;

    public function __construct(Init $init, $contractAddress = null, $provider = null)
    {
        $this->init = $init;
        if ($contractAddress == null) {
            $this->routerAddress = $init->getRouter();
        } else {
            $this->routerAddress = $contractAddress;
        }

        if (isset($provider))
            $this->provider = $provider;
        else
            $this->provider = new CallContractMethods($this->init);
    }

    /**
     * @throws BitStringException
     */
    public function createProvideLiquidityBody(
        Address    $routerWalletAddress,
        BigInteger $minLpOut,
        Address    $receiverAddress,
        Address    $refundAddress,
        bool       $bothPositive,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null
    )
    {
        return (new Builder())
            ->writeUint(OpCodes::PROVIDE_LP, 32)
            ->writeAddress($routerWalletAddress)
            ->writeAddress($refundAddress)
            ->writeAddress($excessesAddress ?? $refundAddress)
            ->writeRef(
                (new Builder())
                    ->writeCoins($minLpOut)
                    ->writeAddress($receiverAddress)
                    ->writeUint($bothPositive ? 1 : 0, 1)
                    ->writeCoins($customPayloadForwardGasAmount ?? 0)
                    ->writeMaybeRef($customPayload)
                    ->cell()
            )
            ->cell();
    }

    /**
     * @throws BitStringException
     */
    public function createCrossProvideLiquidityBody(
        Address    $routerWalletAddress,
        BigInteger $minLpOut,
        Address    $receiverAddress,
        Address    $refundAddress,
        bool       $bothPositive,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null
    )
    {
        return (new Builder())
            ->writeUint(OpCodes::CROSS_PROVIDE_LP, 32)
            ->writeAddress($routerWalletAddress)
            ->writeAddress($refundAddress)
            ->writeAddress($excessesAddress ?? $refundAddress)
            ->writeRef(
                (new Builder())
                    ->writeCoins($minLpOut)
                    ->writeAddress($receiverAddress)
                    ->writeUint($bothPositive ? 1 : 0, 1)
                    ->writeCoins($customPayloadForwardGasAmount ?? 0)
                    ->writeMaybeRef($customPayload)
                    ->cell()
            )
            ->cell();
    }

    /**
     * @throws CellException
     * @throws SliceException
     * @throws BitStringException
     */
    protected function implGetProvideLiquidityTonTxParams(
        Address    $userWalletAddress,
        pTON       $proxyTon,
        Address    $otherTokenAddress,
        BigInteger $sendAmount,
        BigInteger $minLpOut,
        Address    $refundAddress = null,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
        bool       $bothPositive = null,
                   $queryId = null,
    )
    {
        $routerWalletAddress = $this->provider->getWalletAddress($this->routerAddress, $otherTokenAddress);

        $forwardPayload = $this->createProvideLiquidityBody(
            $routerWalletAddress,
            $minLpOut,
            $userWalletAddress,
            $refundAddress ?? $userWalletAddress,
            $bothPositive,
            $excessesAddress,
            $customPayload,
            $customPayloadForwardGasAmount
        );

        $forwardTonAmount = $forwardGasAmount;

        return $proxyTon->getTonTransferTxParams(
            contractAddress: $this->routerAddress,
            tonAmount: $sendAmount,
            destinationAddress: $this->routerAddress,
            refundAddress: $userWalletAddress,
            forwardPayload: $forwardPayload,
            forwardTonAmount: $forwardTonAmount,
            queryId: $queryId ?? 0
        );
    }

    protected function implGetProvideLiquidityJettonTxParams(
        Address    $userWalletAddress,
        Address    $sendTokenAddress,
        Address    $otherTokenAddress,
        BigInteger $sendAmount,
        BigInteger $minLpOut,
        Address    $refundAddress = null,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null,
        bool       $bothPositive = null
    ): TransactionParams
    {
        $jettonWalletAddress = $this->provider->getWalletAddress($userWalletAddress, $sendTokenAddress);
        $routerWalletAddress = $this->provider->getWalletAddress($this->routerAddress, $otherTokenAddress);

        $forwardPayload = $this->createProvideLiquidityBody(
            $routerWalletAddress,
            $minLpOut,
            $userWalletAddress,
            $refundAddress ?? $userWalletAddress,
            $bothPositive,
            $excessesAddress,
            $customPayload,
            $customPayloadForwardGasAmount
        );

        $forwardTonAmount = $forwardGasAmount;

        $body = CreateJettonTransferMessage::create(
            queryId: $queryId ?? 0,
            amount: $sendAmount,
            destination: $this->routerAddress,
            forwardTonAmount: $forwardTonAmount,
            forwardPayload: $forwardPayload,
            responseDestination: $userWalletAddress
        );

        $value = $gasAmount;

        return new TransactionParams($jettonWalletAddress, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    public function getProvideLiquidityJettonTxParams(
        Address    $userWalletAddress,
        Address    $sendTokenAddress,
        Address    $otherTokenAddress,
        BigInteger $sendAmount,
        BigInteger $minLpOut,
        Address    $refundAddress = null,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    )
    {
        return $this->implGetProvideLiquidityJettonTxParams(
            $userWalletAddress,
            $sendTokenAddress,
            $otherTokenAddress,
            $sendAmount,
            $minLpOut,
            $refundAddress,
            $excessesAddress,
            $customPayload,
            $customPayloadForwardGasAmount,
            gasAmount: $gasAmount ?? (new LpJettonGas())->gasAmount,
            forwardGasAmount: $forwardGasAmount ?? (new LpJettonGas())->forwardGasAmount,
            queryId: $queryId,
            bothPositive: true
        );
    }

    public function getSingleSideProvideLiquidityJettonTxParams(
        Address    $userWalletAddress,
        Address    $sendTokenAddress,
        Address    $otherTokenAddress,
        BigInteger $sendAmount,
        BigInteger $minLpOut,
        Address    $refundAddress = null,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    )
    {
        return $this->implGetProvideLiquidityJettonTxParams(
            $userWalletAddress,
            $sendTokenAddress,
            $otherTokenAddress,
            $sendAmount,
            $minLpOut,
            $refundAddress,
            $excessesAddress,
            $customPayload,
            $customPayloadForwardGasAmount,
            gasAmount: $gasAmount ?? (new SingleSideLpJettonGas())->gasAmount,
            forwardGasAmount: $forwardGasAmount ?? (new SingleSideLpJettonGas())->forwardGasAmount,
            queryId: $queryId,
            bothPositive: false
        );
    }

    public function getProvideLiquidityTonTxParams(
        Address    $userWalletAddress,
        pTON       $proxyTon,
        Address    $otherTokenAddress,
        BigInteger $sendAmount,
        BigInteger $minLpOut,
        Address    $refundAddress = null,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
        bool       $bootPositive = null,
        BigInteger $customPayloadForwardGasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    )
    {
        return $this->implGetProvideLiquidityTonTxParams(
            userWalletAddress: $userWalletAddress,
            proxyTon: $proxyTon,
            otherTokenAddress: $otherTokenAddress,
            sendAmount: $sendAmount,
            minLpOut: $minLpOut,
            refundAddress: $refundAddress,
            excessesAddress: $excessesAddress,
            customPayload: $customPayload,
            customPayloadForwardGasAmount: $customPayloadForwardGasAmount,
            forwardGasAmount: $forwardGasAmount ?? (new LpTonGas())->forwardGasAmount,
            bothPositive: true,
            queryId: $queryId
        );
    }

    public function getSingleSideProvideLiquidityTonTxParams(
        Address    $userWalletAddress,
        pTON       $proxyTon,
        Address    $otherTokenAddress,
        BigInteger $sendAmount,
        BigInteger $minLpOut,
        Address    $refundAddress = null,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
        bool       $bootPositive = null,
        BigInteger $customPayloadForwardGasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    )
    {
        return $this->implGetProvideLiquidityTonTxParams(
            userWalletAddress: $userWalletAddress,
            proxyTon: $proxyTon,
            otherTokenAddress: $otherTokenAddress,
            sendAmount: $sendAmount,
            minLpOut: $minLpOut,
            refundAddress: $refundAddress,
            excessesAddress: $excessesAddress,
            customPayload: $customPayload,
            customPayloadForwardGasAmount: $customPayloadForwardGasAmount,
            forwardGasAmount: $forwardGasAmount ?? (new SingleSideLpTonGas())->forwardGasAmount,
            bothPositive: false,
            queryId: $queryId
        );
    }


    /**
     * @throws CellException
     * @throws SliceException
     */
    public function readWalletAddress($item): Address
    {
        return (Cell::oneFromBoc($item[$item['type']])->beginParse()->loadAddress());
    }
}