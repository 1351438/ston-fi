<?php

namespace StonFi\pTON\v1;

use Brick\Math\BigInteger;
use JetBrains\PhpStorm\ArrayShape;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use StonFi\dex\api\v1\Jetton;
use StonFi\dex\common\CreateJettonTransferMessage;
use StonFi\Init;

class PtonV1
{
    private Init $init;
    public Address $address;


    public function __construct($init, $address = null)
    {
        $this->init = $init;
        $this->address = new Address(
            $address ?? "EQCM3B12QK1e4yZSf8GtBRT0aLMNyEsBc_DhVfRRtOEffLez",
        );
    }

    /**
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     */
    #[ArrayShape(["address" => "string", "payload" => "string", "value" => "\Brick\Math\BigInteger"])] public
    function getTonTransferTxParams(
        Address $contractAddress,
        BigInteger $tonAmount,
        Address $destinationAddress,
        Address $refundAddress,
        Cell $forwardPayload = null,
        BigInteger $forwardTonAmount = null,
        $queryId = null,
    )
    {
        $jetton = new Jetton($this->init);
        $to = json_decode($jetton->jettonWalletAddress( $destinationAddress->toString(),$this->address->toString()), true)['address'];
        if (Address::isValid($to)) {
            $to = new Address($to);
        } else {
            throw new \Exception("Couldn't generate address");
        }

        $body = CreateJettonTransferMessage::create(
            queryId: $queryId ?? 0,
            amount: $tonAmount,
            destination: $destinationAddress,
            forwardTonAmount: ($forwardTonAmount ?? BigInteger::of(0)),
            forwardPayload: $forwardPayload,
        );

        $value = BigInteger::sum($tonAmount, ($forwardTonAmount ?? BigInteger::of(0)));

        return [
            "address" => $to->toString(),
            "payload" => Bytes::bytesToBase64($body->toBoc()),
            "amount" => $value
        ];
    }
}