<?php

namespace StonFi\pTON\v1;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Grpc\Call;
use JetBrains\PhpStorm\ArrayShape;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use StonFi\const\v1\models\TransactionParams;
use StonFi\contracts\api\v1\Jetton;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\common\CreateJettonTransferMessage;
use StonFi\Init;
use StonFi\pTON\pTON;

class PtonV1 extends pTON
{
    private Init $init;
    public Address $address;
    public CallContractMethods $provider;

    public function __construct($init, $address = null, CallContractMethods $provider = null)
    {
        if ($provider != null)
            $this->provider = $provider;
        else
            $this->provider = new CallContractMethods($init);

        $this->init = $init;
        $this->address = new Address(
            $address ?? "EQCM3B12QK1e4yZSf8GtBRT0aLMNyEsBc_DhVfRRtOEffLez",
        );
    }

    /**
     * @throws \Olifanton\Interop\Boc\Exceptions\BitStringException
     * @throws MathException
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
        $to = $this->provider->getWalletAddress($destinationAddress->toString(), $this->address->toString());


        $body = CreateJettonTransferMessage::create(
            queryId: $queryId ?? 0,
            amount: $tonAmount,
            destination: $destinationAddress,
            forwardTonAmount: ($forwardTonAmount ?? BigInteger::of(0)),
            forwardPayload: $forwardPayload,
        );

        $value = BigInteger::sum($tonAmount, ($forwardTonAmount ?? BigInteger::of(0)));

        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }
}