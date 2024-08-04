<?php

namespace StonFi\pTON;
use Olifanton\Interop\Address;
use StonFi\contracts\common\CallContractMethods;
use StonFi\Init;

abstract class pTON {
    public Address $address;
    public CallContractMethods $provider;
}