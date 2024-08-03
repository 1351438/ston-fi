<?php

namespace StonFi\const\v1\gas\pool;

use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class CollectFeeGas extends EstimateGas
{
    public function __construct()
    {
        parent::__construct(Units::toNano("1.1"), Units::toNano(0));
    }
}