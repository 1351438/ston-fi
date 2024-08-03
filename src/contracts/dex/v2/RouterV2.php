<?php

namespace StonFi\contracts\dex\v2;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Boc\Exceptions\SliceException;
use Olifanton\Interop\Boc\SnakeString;
use StonFi\const\v1\models\PoolData;
use StonFi\const\v2\models\RouterData;
use StonFi\const\v2\models\RouterVersion;
use StonFi\contracts\common\CallContractMethods;
use StonFi\Init;

class RouterV2
{
    private Init $init;
    private Address $routerAddress;
    private CallContractMethods $provider;

    public function __construct(Init $init, Address $contractAddress = null, CallContractMethods $provider = null)
    {
        if ($provider != null) {
            $this->provider = $provider;
        } else {
            $this->provider = new CallContractMethods($init);
        }
        $this->init = $init;
        if ($contractAddress == null) {
            $this->routerAddress = $init->getRouter();
        } else {
            $this->routerAddress = $contractAddress;
        }
    }

    public function getRouterVersion()
    {
        if ($this->routerAddress == null)
            throw  new \Exception("Router address is required.");

        $result = json_decode($this->provider->runMethod($this->routerAddress, "get_router_data"), true);

        if ($result['success']) {
            $stacks = $result['stack'];
            if (count($stacks) != 3)
                throw new \Exception("Stack under/overflow");

            $major = BigInteger::of(hexdec($stacks[0][$stacks[0]['type']]));
            $minor = (($stacks[1][$stacks[1]['type']]));
            $development = $stacks[2][$stacks[2]['type']];

            return new RouterVersion($major, $minor, $development);
        } else {
            throw new \Exception("Contract getter failed.");
        }
    }

    public function getRouterData()
    {
        if ($this->routerAddress == null)
            throw  new \Exception("Router address is required.");

        $result = json_decode($this->provider->runMethod($this->routerAddress, "get_router_data"), true);

        if ($result['success']) {
            $stacks = $result['stack'];
            if (count($stacks) != 9)
                throw new \Exception("Stack under/overflow");
            $routerId = BigInteger::of(hexdec($stacks[0][$stacks[0]['type']]));
            $dexType = SnakeString::parse(($stacks[1][$stacks[1]['type']]));
            $isLocked = hexdec($stacks[2][$stacks[2]['type']]) == 1;
            $adminAddress = $this->readWalletAddress($stacks[3]);
            $tempUpgrade = Cell::oneFromBoc($stacks[4][$stacks[4]['type']]);
            $poolCode = Cell::oneFromBoc($stacks[5][$stacks[5]['type']]);
            $jettonLpWalletCode = Cell::oneFromBoc($stacks[6][$stacks[6]['type']]);
            $lpAccountCode = Cell::oneFromBoc($stacks[7][$stacks[7]['type']]);
            $vaultCode = Cell::oneFromBoc($stacks[8][$stacks[8]['type']]);

            return new RouterData($routerId, $dexType, $isLocked, $adminAddress, $tempUpgrade, $poolCode, $jettonLpWalletCode, $lpAccountCode, $vaultCode);
        } else {
            throw new \Exception("Contract getter failed.");
        }
    }


    /**
     * @throws CellException
     * @throws SliceException
     */
    public function readWalletAddress($item): Address
    {
        return (Cell::oneFromBoc($item[$item['type']])->beginParse()->loadAddress());
    }
}