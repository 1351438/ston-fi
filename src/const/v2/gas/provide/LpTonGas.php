<?php
namespace StonFi\const\v2\gas\provide;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class LpTonGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(null, Units::toNano('0.3'));
    }
}