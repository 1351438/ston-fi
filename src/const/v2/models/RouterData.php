<?php

namespace StonFi\const\v2\models;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;

class  RouterData
{
    public function __construct(
        public readonly BigInteger $routerId,
        public readonly string $dexType,
        public readonly bool $isLocked,
        public readonly Address $adminAddress,
        public readonly Cell $tempUpgrade,
        public readonly Cell $poolCode,
        public readonly Cell $jettonLpWalletCode,
        public readonly Cell $lpAccountCode,
        public readonly Cell $vaultCode,
    )
    {
    }
}