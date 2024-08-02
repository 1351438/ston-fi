<?php

namespace StonFi\const\v1\gas\provide;

use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class SingleSideLpTonGas extends EstimateGas
{
    public function __construct()
    {
        parent::__construct(null, Units::toNano('0.8'));
    }
}