<?php
namespace StonFi\const\gas\swap;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class JettonToJettonGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("0.3"), Units::toNano('0.24'));
    }
}