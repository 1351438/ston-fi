<?php

namespace StonFi\const\v2\gas\pool;

use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class CollectFeeGas extends EstimateGas
{
    public function __construct()
    {
        parent::__construct(Units::toNano("0.4"), Units::toNano(0));
    }
}