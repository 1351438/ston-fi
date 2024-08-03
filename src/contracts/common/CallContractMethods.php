<?php

namespace StonFi\contracts\common;

use Exception;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Boc\Exceptions\SliceException;
use Olifanton\Interop\Bytes;
use StonFi\enums\Networks;
use StonFi\Init;
use function PHPUnit\Framework\isNull;

class CallContractMethods
{
    public $url;

    public function __construct(public readonly Init $init)
    {
        $this->url = $this->init->getNetwork() == Networks::MAINNET ? "https://tonapi.io/" : "https://testnet.tonapi.io/";
    }

    public function call($contractAddress, $method, $inputs = [])
    {
        $link = $this->url . "v2/blockchain/accounts/$contractAddress/methods/$method" . $this->convertArguments($inputs);
        return $this->init->apiRequest($link);
    }


    /**
     * @throws CellException
     * @throws SliceException
     * @throws BitStringException
     * @throws Exception
     */
    public function getWalletAddress(string $userAddress, string $jettonAddress)
    {
        $result = ($this->call($jettonAddress, "get_wallet_address", [
            Bytes::bytesToHexString((new Builder())->writeAddress(new Address($userAddress))->cell()->toBoc(false))
        ]));

        $result = json_decode($result, true);

        if ($result['success']) {
            $stack = $result['stack'];
            $cell = $stack[0][$stack[0]['type']];

            $addr = Cell::oneFromBoc($cell)->beginParse()->loadAddress();

            if (Address::isValid($addr)) {
                return $addr;
            } else {
                throw new \Exception("Invalid jetton wallet address");
            }
        } else {
            throw new Exception("Error binding jetton wallet address: ". json_encode($result));
        }
    }

    private function convertArguments($inputs)
    {

        $args = '';
        if (count($inputs) > 0) {
            $args .= "?";
            $c = 0;
            foreach ($inputs as $i) {
                $args .= "args=" . $i;
                if ($c != count($inputs) - 1) {
                    $args .= '&';
                }
                $c++;
            }
        }
        return $args;
    }

    public function readWalletAddressFromCell($hex): Address
    {
        return new Address(Cell::oneFromBoc($hex)->beginParse()->loadAddress());
    }
    public function readWalletAddressFromStack($item): Address
    {
        return $this->readWalletAddressFromCell($item[$item['type']]);
    }
}