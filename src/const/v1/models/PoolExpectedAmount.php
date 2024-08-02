<?php

namespace StonFi\const\v1\models;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;

class  PoolExpectedAmount
{
    public function __construct(
        public readonly string $amount0,
        public readonly string $amount1,
    )
    {
    }

    public function toArray()
    {
        return [
            'amount0' => $this->amount0,
            'amount1' => $this->amount1,
        ];
    }

    public function toMap()
    {
        return json_encode($this->toArray(), 128);
    }
}