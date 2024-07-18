<?php
declare(strict_types=1);

namespace Ton\StonFi\dex\api\v1;

use Ton\StonFi\Enums\Methods;
use Ton\StonFi\Init;

class Assets
{
    private $init;

    public function __construct(Init $init)
    {
        $this->init = $init;
    }

    public function assets()
    {
        return $this->init->endpoint('/v1/assets');
    }

    public function query($condition, $walletAddress, $unconditionalAssets = [])
    {
        return $this->init->endpoint("/v1/assets/query?" . (isset($condition) ? "condition=$condition" : "") . (isset($walletAddress) ? "wallet_address=$walletAddress" : "") . (isset($unconditionalAssets) ? "unconditional_assets=" . implode(",", $unconditionalAssets) : ""),Methods::POST);
    }
    public function search($searchString, $condition = null, $walletAddress = null)
    {
        return $this->init->endpoint("/v1/assets/query?" . (isset($condition) ? "condition=$condition" : "") . (isset($walletAddress) ? "wallet_address=$walletAddress" : "") . (isset($searchString) ? "search_string=" . $searchString : ""), Methods::POST);
    }
    public function address($walletAddress)
    {
        return $this->init->endpoint('/v1/assets/' . $walletAddress);
    }
}