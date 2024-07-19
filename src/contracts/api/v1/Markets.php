<?php
declare(strict_types=1);

namespace StonFi\contracts\api\v1;

use StonFi\Init;

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