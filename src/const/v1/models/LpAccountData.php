<?php

namespace StonFi\const\v1\models;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;

class  LpAccountData
{
    public function __construct(
        public readonly Address    $userAddress,
        public readonly Address    $poolAddress,
        public readonly BigInteger $amount0,
        public readonly BigInteger $amount1,
    )
    {}

    public function toMap()
    {
        return json_encode([
            'userAddress' => $this->userAddress->toString(),
            'poolAddress' => $this->poolAddress->toString(),
            'amount0' => $this->amount0,
            'amount1' => $this->amount1,
        ], 128);
    }
}