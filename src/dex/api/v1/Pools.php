<?php
declare(strict_types=1);

namespace Ton\StonFi\dex\api\v1;

use Ton\StonFi\Init;

class Pools
{
    private $init;

    public function __construct(Init $init)
    {
        $this->init = $init;
    }

    public function pools()
    {
        return $this->init->endpoint('/v1/pools');
    }
    public function address($walletAddress)
    {
        return $this->init->endpoint('/v1/pools/' . $walletAddress);
    }
}