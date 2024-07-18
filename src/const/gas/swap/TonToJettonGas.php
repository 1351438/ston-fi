<?php
namespace StonFi\const\gas\swap;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class TonToJettonGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(null, Units::toNano('0.24'));
    }
}