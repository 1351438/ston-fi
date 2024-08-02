<?php
namespace StonFi\const\v1\gas\swap;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class JettonToJettonGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("0.22"), Units::toNano('0.175'));
    }
}