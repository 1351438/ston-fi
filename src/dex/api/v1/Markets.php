<?php
declare(strict_types=1);

namespace Ton\StonFi\dex\api\v1;

use Ton\StonFi\Init;

class Markets
{
    private $init;

    public function __construct(Init $init)
    {
        $this->init = $init;
    }

    public function markets()
    {
        return $this->init->endpoint('/v1/markets');
    }
}