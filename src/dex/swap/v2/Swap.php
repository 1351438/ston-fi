<?php
declare(strict_types=1);

namespace Ton\StonFi\dex\swap\v2;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use Ton\StonFi\const\gas\swap\JettonToJettonGas;
use Ton\StonFi\dex\api\v1\Jetton;
use Ton\StonFi\Init;

class Swap
{
    private Init $init;

    public function __construct(Init $init)
    {
        $this->init = $init;
    }

    public function simulate($from, $to, $units, $slippageTolerance)
    {
        $swap = new \Ton\StonFi\dex\api\v1\Swap($this->init);
        return $swap->swapSimulate($from, $to, $units, $slippageTolerance);
    }

    public function JettonToJetton(
        Address $contractAddress,
        Address $userWalletAddress,
        Address $offerJettonAddress,
        Address $askJettonAddress,
        $offerAmount,
        $minAskAmount,
        Address $refundAddress = null,
        Address $excessesAddress = null,
        Address $referralAddress = null,
        $referralValue = null,
        Cell $customPayload = null,
        $customPayloadForwardGasAmount = null,
        Cell $refundPayload = null,
        $refundForwardGasAmount = null,
        $gasAmount = null,
        $forwardGasAmount = null,
        $queryId = null
    )
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

        $body = $this->createJettonTransferMessage(
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
    function JettonToTon($from, $to, $units, $slippageTolerance)
    {

    }

    public
    function TonToJetton($from, $to, $units, $slippageTolerance)
    {

    }

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
            ->writeUint(DEX_OPCODES['SWAP'], 32)
            ->writeAddress($askJettonWalletAddress)
            ->writeAddress($refundAddress)
            ->writeAddress($excessesAddress ?? $refundAddress)
            ->writeRef($swapData->cell());

        return $c->cell();
    }

    public function createJettonTransferMessage(
        $queryId,
        $amount,
        Address $destination,
        $forwardTonAmount,
        Cell $forwardPayload = null,
        Cell $customPayload = null,
        Address $responseDestination = null
    )
    {
        $c = new Builder();
        $c
            ->writeUint(0xf8a7ea5, 32)
            ->writeUint($queryId, 64)
            ->writeCoins($amount)
            ->writeAddress($destination)
            ->writeAddress($responseDestination ?? null);
        if ($customPayload != null) {
            $c->writeBit(true);
            $c->writeRef($customPayload);
        } else {
            $c->writeBit(false);
        }
        $c->writeCoins($forwardTonAmount ?? 0);
        if ($forwardPayload != null) {
            $c->writeBit(true);
            $c->writeRef($forwardPayload);
        } else {
            $c->writeBit(false);
        }
        return $c->cell();
    }
}