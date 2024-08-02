<?php

namespace StonFi\const\v1\models;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;

class  PoolExpectedOutput
{
    public function __construct(
        public readonly string $jettonToReceive,
        public readonly string $protocolFeePaid,
        public readonly string $refFeePaid,
    )
    {
    }

    public function toArray()
    {
        return [
            'jetton_to_receive' => $this->jettonToReceive,
            'protocol_fee_paid' => $this->protocolFeePaid,
            'ref_fee_paid' => $this->refFeePaid,
        ];
    }

    public function toMap()
    {
        return json_encode($this->toArray(), 128);
    }
}