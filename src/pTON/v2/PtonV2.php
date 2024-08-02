<?php

namespace StonFi\pTON\v2;

use Brick\Math\BigInteger;
use JetBrains\PhpStorm\ArrayShape;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use StonFi\const\v1\gas\transfer\TonTransferGas;
use StonFi\const\v1\models\TransactionParams;
use StonFi\const\OpCodes;
use StonFi\contracts\api\v1\Jetton;
use StonFi\Init;

class PtonV2
{
    private Init $init;
    public Address $address;


    public function __construct($init, $address)
    {
        $this->init = $init;
        $this->address = new Address(
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
    #[ArrayShape(["address" => "string", "payload" => "string", "value" => "\Brick\Math\BigInteger"])] public function getTonTransferTxParams(
        Address    $contractAddress,
        BigInteger $tonAmount,
        Address    $destinationAddress,
        Address    $refundAddress,
        Cell       $forwardPayload = null,
        BigInteger $forwardTonAmount = null,
                   $queryId = null,
    ): TransactionParams
    {
        $jetton = new Jetton($this->init);
        $to = json_decode($jetton->jettonWalletAddress($contractAddress->toString(), $destinationAddress->toString()), true)['address'];
        if (Address::isValid($to)) {
            $to = new Address($to);
        } else {
            throw new \Exception("Couldn't generate address");
        }

        $body = $this->createTonTransferBody(
            tonAmount: $tonAmount,
            refundAddress: $refundAddress,
            forwardPayload: $forwardPayload,
            queryId: $queryId
        );

        $value = BigInteger::sum($tonAmount, ($forwardTonAmount ?? BigInteger::of(0)), (new TonTransferGas())->gasAmount);

        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }
}