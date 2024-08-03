<?php

namespace StonFi\const\v2\gas\pool;

use Olifanton\Interop\Units;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;
use StonFi\const\EstimateGas;

class BurnGas extends EstimateGas
{
    public function __construct()
    {
        parent::__construct(Units::toNano("0.8"), Units::toNano(0));
    }
}