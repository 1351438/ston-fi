<?php
namespace StonFi\const\v1\gas\swap;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class JettonToTonGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("0.17"), Units::toNano('0.125'));
    }
}