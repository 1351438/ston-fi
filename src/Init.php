<?php
declare(strict_types=1);

namespace StonFi;

use Olifanton\Interop\Address;
use Http\Client\Common\HttpMethodsClient;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Olifanton\Ton\Transports\Toncenter\ToncenterHttpV2Client;
use Olifanton\Ton\Transports\Toncenter\ClientOptions;
use Olifanton\Ton\Transports\Toncenter\ToncenterTransport;
use StonFi\enums\Methods;
use StonFi\enums\Networks;

class Init
{
    private Networks $network;
    private Address $router;
    private Address $tonAddress;
    private string $apiEndpoint;

    /**
     * @throws \Exception
     */
    public function __construct(Networks $network)
    {
        $this->network = $network;
        $this->tonAddress = new Address('EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c');
        $this->router = new Address($network == Networks::MAINNET ? 'EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt' : 'EQBsGx9ArADUrREB34W-ghgsCgBShvfUr4Jvlu-0KGc33Rbt');
        if ($network == Networks::MAINNET) {
            $this->apiEndpoint = "https://api.ston.fi";
        } else {
            throw new \Exception("Test net does not support");
        }
    }


    /**
     * @throws \Exception
     */
    public function endpoint($action, Methods $method = Methods::GET, $params = [], $headers = [])
    {
        return $this->apiRequest($this->apiEndpoint . $action, $method, $params, $headers);
    }

    /**
     * @throws \Exception
     */
    public function apiRequest($url, Methods $method = Methods::GET, $params = [], $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($method == Methods::POST) {

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        if (!is_array(json_decode($server_output, true)))
            throw new \Exception("Error: " . $server_output);
        return ($server_output);
    }


    /**
     * @return Networks
     */
    public function getNetwork(): Networks
    {
        return $this->network;
    }

    /**
     * @return Address
     */
    public function getRouter(): Address
    {
        return $this->router;
    }

    /**
     * @return Address
     */
    public function getTonAddress(): Address
    {
        return $this->tonAddress;
    }
}