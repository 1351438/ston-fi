<?php
namespace StonFi\const\gas\provide;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class LpJettonGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("0.3"), Units::toNano('0.235'));
    }
}