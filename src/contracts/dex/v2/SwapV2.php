<?php
declare(strict_types=1);

namespace StonFi\contracts\dex\v2;

use Brick\Math\BigInteger;
use JetBrains\PhpStorm\ArrayShape;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\SliceException;
use Olifanton\Interop\Bytes;
use Olifanton\Ton\Connect\Request\Transaction;
use StonFi\const\v2\gas\swap\JettonToJettonGas;
use StonFi\const\v2\gas\swap\JettonToTonGas;
use StonFi\const\v2\gas\swap\TonToJettonGas;
use StonFi\const\v2\models\TransactionParams;
use StonFi\const\OpCodes;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\common\CreateJettonTransferMessage;
use StonFi\Init;
use StonFi\pTON\pTON;

class SwapV2
{
    private Init $init;
    private Address $routerAddress;
    private CallContractMethods $provider;

    public function __construct(Init $init, Address $contractAddress = null, CallContractMethods $provider = null)
    {
        if ($provider != null) {
            $this->provider = $provider;
        } else {
            $this->provider = new CallContractMethods($init);
        }
        $this->init = $init;
        if ($contractAddress == null) {
            $this->routerAddress = $init->getRouter();
        } else {
            $this->routerAddress = $contractAddress;
        }
    }

    /**
     * @return Address
     */
    public function getRouterAddress(): Address
    {
        return $this->routerAddress;
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
     * @throws SliceException
     */
    public function JettonToJettonTxParams(
        Address     $userWalletAddress,
        Address     $offerJettonAddress,
        Address     $askJettonAddress,
        BigInteger  $offerAmount,
        BigInteger  $minAskAmount,
        Address    $refundAddress = null,
        Address    $excessesAddress = null,
        Address    $referralAddress = null,
        BigInteger $referralValue = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        Cell       $refundPayload = null,
        BigInteger $refundForwardGasAmount = null,
        BigInteger $gasAmount = null,
        BigInteger $forwardGasAmount = null,
                    $queryId = null
    ): TransactionParams
    {
        $offerJettonWalletAddress = $this->provider->getWalletAddress($userWalletAddress->toString(), $offerJettonAddress->toString());
        $askJettonWalletAddress = $this->provider->getWalletAddress($this->routerAddress->toString(), $askJettonAddress->toString());

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

    public
    function JettonToTonTxParams(
        pTON     $proxyTon,
        Address    $userWalletAddress,
        Address    $offerJettonAddress,
        BigInteger $offerAmount,
        BigInteger $minAskAmount,
        Address    $refundAddress = null,
        Address    $excessAddress = null,
        Address    $referralAddress = null,
        BigInteger $referralValue = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        Cell       $refundPayload = null,
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

    function TonToJettonTxParams(
        pTON     $proxyTon,
        Address    $userWalletAddress,
        Address    $askJettonAddress,
        BigInteger $offerAmount,
        BigInteger $minAskAmount,
        Address    $refundAddress = null,
        Address    $excessAddress = null,
        Address    $referralAddress = null,
        BigInteger $referralValue = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        Cell       $refundPayload = null,
        BigInteger $refundForwardGasAmount = null,
        BigInteger $forwardGasAmount = null,
                   $queryId = null
    )
    {
        $askJettonWalletAddress = $this->provider->getWalletAddress($this->routerAddress->toString(), $askJettonAddress->toString());

        $forwardPayload = $this->createSwapBody(
            askJettonWalletAddress: $askJettonWalletAddress,
            receiverAddress: $userWalletAddress,
            refundAddress: $refundAddress ?? $userWalletAddress,
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
     * @throws \Exception
     */
    public
    function createSwapBody(
        Address    $askJettonWalletAddress,
        Address    $receiverAddress,
        Address    $refundAddress,
                   $minAskAmount,
        Address    $excessesAddress = null,
        Cell       $customPayload = null,
                   $customPayloadForwardGasAmount = null,
                   $refundPayload = null,
                   $refundForwardGasAmount = null,
        Address    $referralAddress = null,
        BigInteger $referralValue = null
    ): Cell
    {
        if ($referralValue != null && ($referralValue->isLessThan(0) || $referralValue->isGreaterThan(100))) {
            throw new \Exception("Referral value should be in rang 0-100 BPS");
        }
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

    /**
     * @throws BitStringException
     */
    public function createCrossSwapBody(
        Address    $askJettonWalletAddress,
        Address    $receiverAddress,
        BigInteger $minAskAmount,
        Address    $refundAddress,
        Address    $excessAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        Cell       $refundPayload = null,
        BigInteger $refundForwardGasAmount = null,
        Address    $referralAddress = null,
        BigInteger $referralValue = null
    ): Cell
    {
        if ($referralValue != null && ($referralValue->isLessThan(0) || $referralValue->isGreaterThan(100))) {
            throw new \Exception("Referral value should be in rang 0-100 BPS");
        }

        $payload =
            (new Builder())
                ->writeCoins($minAskAmount)
                ->writeAddress($receiverAddress)
                ->writeCoins($customPayloadForwardGasAmount ?? 0)
                ->writeMaybeRef($customPayload)
                ->writeCoins($refundForwardGasAmount ?? 0)
                ->writeMaybeRef($refundPayload)
                ->writeUint($referralValue ?? BigInteger::of(10), 16)
                ->writeAddress($referralAddress ?? null)
                ->cell();
        return (new Builder())
            ->writeUint(OpCodes::CROSS_SWAP, 32)
            ->writeAddress($askJettonWalletAddress)
            ->writeAddress($refundAddress)
            ->writeAddress($excessAddress ?? $refundAddress)
            ->writeRef($payload)->cell();
    }
}