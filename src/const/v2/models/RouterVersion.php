<?php

namespace StonFi\const\v2\models;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;

class  RouterVersion
{
    public function __construct(
        public readonly BigInteger $major,
        public readonly BigInteger $minor,
        public readonly string $development
    )
    {
    }
}