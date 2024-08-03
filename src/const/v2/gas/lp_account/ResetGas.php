<?php

namespace StonFi\const\v2\gas\lp_account;

use Olifanton\Interop\Units;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;
use StonFi\const\EstimateGas;

class ResetGas extends EstimateGas
{
    public function __construct()
    {
        parent::__construct(Units::toNano("0.2"), Units::toNano(0));
    }
}