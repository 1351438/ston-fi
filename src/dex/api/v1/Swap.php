<?php
declare(strict_types=1);

namespace StonFi\dex\api\v1;

use StonFi\Enums\Methods;
use StonFi\Init;

class Swap
{
    private $init;

    public function __construct(Init $init)
    {
        $this->init = $init;
    }

    public function reverseSwapSimluate($offerAddress, $askAddress, $units, $slippageTolerance, $referralAddress = null)
    {
        return $this->init->endpoint("/v1/reverse_swap/simulate?offer_address=$offerAddress&ask_address=$askAddress&units=$units&slippage_tolerance=$slippageTolerance". (isset($referralAddress) ? "&referral_address=$referralAddress" : ""), Methods::POST);
    }

    public function swapSimulate($offerAddress, $askAddress, $units, $slippageTolerance, $referralAddress = null)
    {
        return $this->init->endpoint("/v1/swap/simulate?offer_address=$offerAddress&ask_address=$askAddress&units=$units&slippage_tolerance=$slippageTolerance". (isset($referralAddress) ? "&referral_address=$referralAddress" : ""), Methods::POST);
    }

    public function swapStatus($routerAddress, $ownerAddress, $queryId)
    {
        $params = "?router_address=$routerAddress&owner_address=$ownerAddress&query_id=$queryId";
        return $this->init->endpoint('/v1/swap/status' . $params);
    }
}