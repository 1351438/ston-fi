<?php
declare(strict_types=1);

namespace Ton\StonFi\dex\api\v1;

use Ton\StonFi\Init;

class Farms
{
    private $init;

    public function __construct(Init $init)
    {
        $this->init = $init;
    }

    public function farms()
    {
        return $this->init->endpoint('/v1/farms');
    }
    public function address($walletAddress)
    {
        return $this->init->endpoint('/v1/farms/' . $walletAddress);
    }
    public function farms_by_pool($poolAddress)
    {
        return $this->init->endpoint('/v1/farms_by_pool/' . $poolAddress);
    }
}