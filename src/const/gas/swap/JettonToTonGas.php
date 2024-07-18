<?php
namespace Ton\StonFi\const\gas\swap;
use Olifanton\Interop\Units;
use Ton\StonFi\const\EstimateGas;

class JettonToTonGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("0.3"), Units::toNano('0.24'));
    }
}