<?php

namespace StonFi\const\v2\models;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;

class  VaultData
{
    public function __construct(
        public readonly Address $ownerAddress,
        public readonly Address $tokenAddress,
        public readonly Address $routerAddress,
        public readonly BigInteger $depositedAmount,
    )
    {
    }
}