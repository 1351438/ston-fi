<?php

namespace StonFi\const\v2\models;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;

class  TransactionParams
{
    public function __construct(
        public readonly Address $address,
        public readonly string $payload,
        public readonly BigInteger $value,
    )
    {}

    public function toArray()
    {
        return [
            'address' => $this->address->toString(false, true, false),
            'payload' => $this->payload,
            'value' => $this->value->__toString(),
        ];
    }

    public function toMap()
    {
        return json_encode($this->toArray(), 128);
    }
}