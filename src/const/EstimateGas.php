<?php

namespace Ton\StonFi\const;
use Brick\Math\BigInteger;
abstract class EstimateGas
{
    public BigInteger|int|null $gasAmount = 0;
    public BigInteger|int $forwardGasAmount = 0;

    public function __construct($gasAmount, $forwardGasAmount)
    {
        $this->gasAmount = $gasAmount;
        $this->forwardGasAmount = $forwardGasAmount;
    }
}
