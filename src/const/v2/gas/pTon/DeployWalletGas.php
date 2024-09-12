<?php
namespace StonFi\const\v2\gas\pTon;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class DeployWalletGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("0.1"), Units::toNano("0"));
    }
}