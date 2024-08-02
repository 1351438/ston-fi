<?php

namespace StonFi\const\v1\gas\pool;

use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class BurnGas extends EstimateGas
{
    public function __construct()
    {
        parent::__construct(Units::toNano("0.5"), null);
    }
}