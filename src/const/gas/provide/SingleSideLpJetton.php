<?php
namespace Ton\StonFi\const\gas\provide;
use Olifanton\Interop\Units;
use Ton\StonFi\const\EstimateGas;

class SingleSideLpJetton extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("1"), Units::toNano('0.8'));
    }
}