<?php
namespace StonFi\const\v2\gas\vault;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class WithdrawFeeGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("0.3"), Units::toNano("0"));
    }
}