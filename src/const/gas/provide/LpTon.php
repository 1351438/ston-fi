<?php
namespace StonFi\const\gas\provide;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class LpTon extends EstimateGas {
    public function __construct()
    {
        parent::__construct(null, Units::toNano('0.3'));
    }
}