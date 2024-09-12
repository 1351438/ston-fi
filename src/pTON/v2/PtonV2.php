<?php

namespace StonFi\pTON\v2;

use Brick\Math\BigInteger;
use JetBrains\PhpStorm\ArrayShape;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Bytes;
use StonFi\const\v2\gas\transfer\TonTransferGas;
use StonFi\const\v1\models\TransactionParams;
use StonFi\const\OpCodes;
use StonFi\const\v2\gas\pTon\DeployWalletGas;
use StonFi\contracts\api\v1\Jetton;
use StonFi\contracts\common\CallContractMethods;
use StonFi\Init;
use StonFi\pTON\pTON;

class PtonV2 extends pTON
{
    private Init $init;
    public Address $address;
    public CallContractMethods $provider;


    public function __construct($init, Address|string $address, CallContractMethods $provider = null)
    {
        if ($provider != null)
            $this->provider = $provider;
        else
            $this->provider = new CallContractMethods($init);

        $this->init = $init;
        $this->address = is_string($address) ? $address : new Address(
            $address,
        );
    }

    /**
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     */
    public function createTonTransferBody(
        BigInteger $tonAmount,
        Address    $refundAddress,
        Cell       $forwardPayload = null,
                   $queryId = null
    )
    {
        $builder = new Builder();
        $builder
            ->writeUint(OpCodes::TON_TRANSFER, 32)
            ->writeUint($queryId ?? 0, 64)
            ->writeCoins($tonAmount)
            ->writeAddress($refundAddress);
        if (isset($forwardPayload)) {
            $builder
                ->writeBit(true)
                ->writeRef($forwardPayload);
        }

        return $builder->cell();
    }

    /**
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     */
    public function getTonTransferTxParams(
        Address    $contractAddress,
        BigInteger $tonAmount,
        Address    $destinationAddress,
        Address    $refundAddress,
        Cell       $forwardPayload = null,
        BigInteger $forwardTonAmount = null,
                   $queryId = null,
    ): TransactionParams
    {
        $to = $this->provider->getWalletAddress($contractAddress->toString(), $destinationAddress->toString());


        $body = $this->createTonTransferBody(
            tonAmount: $tonAmount,
            refundAddress: $refundAddress,
            forwardPayload: $forwardPayload,
            queryId: $queryId
        );

        $value = BigInteger::sum($tonAmount, ($forwardTonAmount ?? BigInteger::of(0)), (new TonTransferGas())->gasAmount);

        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }


    /**
     * @throws BitStringException
     */
    public function createDeployWalletBody(
        Address $ownerAddress,
        Address $excessAddress,
                $queryId = null
    ): Cell
    {
        return (new Builder())
            ->writeUint(OpCodes::DEPLOY_WALLET_V2, 32)
            ->writeUint($queryId ?? 0, 64)
            ->writeAddress($ownerAddress)
            ->writeAddress($excessAddress)
            ->cell();
    }

    /**
     * @throws CellException
     */
    public function getDeployWalletTxParams(
        Address    $ownerAddress,
        Address    $excessAddress = null,
        BigInteger $gasAmount = null,
                   $queryId = null,
    ): TransactionParams
    {
        $to = $this->address;
        $body = $this->createDeployWalletBody(
            ownerAddress: $ownerAddress,
            excessAddress: $excessAddress ?? $ownerAddress,
            queryId: $queryId);
        $value = $gasAmount ?? (new DeployWalletGas())->gasAmount;

        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }
}