<?php
namespace StonFi\const\v1\gas\transfer;
use Olifanton\Interop\Units;
use StonFi\const\EstimateGas;

class TonTransferGas extends EstimateGas {
    public function __construct()
    {
        parent::__construct(Units::toNano("0.01"), Units::toNano("0.01"));
    }
}