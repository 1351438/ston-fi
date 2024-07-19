<?php
declare(strict_types=1);

namespace StonFi\contracts\api\v1;

use StonFi\Init;

class Jetton
{
    private $init;

    public function __construct(Init $init)
    {
        $this->init = $init;
    }

    public function jettonWalletAddress($userAddress, $jettonMaster)
    {
        return $this->init->endpoint('/v1/jetton/' . $jettonMaster . '/address?owner_address=' . $userAddress);
    }
}