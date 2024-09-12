<?php
namespace StonFi\const\v1\gas\pTon;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class DeployWalletGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("1.05"), Units::toNano("0"));
    }
}