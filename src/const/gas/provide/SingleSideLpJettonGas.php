<?php
namespace StonFi\const\gas\provide;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class SingleSideLpJettonGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("1"), Units::toNano('0.8'));
    }
}