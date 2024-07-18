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
    private HttpMethodsClient $httpClient;
    private ToncenterTransport $transport;
    private Address $tonAddress;
    private string $apiEndpoint;


    /**
     * @throws \Exception
     */
    public function __construct(Networks $network, $toncenterApiKey = null)
    {
        $this->network = $network;
        $this->tonAddress = new Address('EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c');
        $this->router = new Address($network == Networks::MAINNET ? 'EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt' : 'EQBsGx9ArADUrREB34W-ghgsCgBShvfUr4Jvlu-0KGc33Rbt');
        if ($toncenterApiKey == null) {
            throw new \Exception("Please justify your TonCenter api key, Request API key from https://t.me/tontestnetapibot or https://t.me/tonapibot");
        } else {
            $this->initHttpClient($toncenterApiKey);
        }
        if ($network == Networks::MAINNET) {
            $this->apiEndpoint = "https://api.ston.fi";
        } else {
            throw new \Exception("Test net does not support");
        }
    }

    private function initHttpClient($toncenterApiKey): void
    {
        $httpClient = new HttpMethodsClient(
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $toncenter = new ToncenterHttpV2Client(
            $httpClient,
            new ClientOptions(
                $this->network == Networks::MAINNET ? "https://toncenter.com/api/v2" : "https://testnet.toncenter.com/api/v2",
                $toncenterApiKey,
            ),
        );
        $this->transport = new ToncenterTransport($toncenter);
        $this->httpClient = $httpClient;
    }

    public function endpoint($action, Methods $method = Methods::GET, $params = [], $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint . $action);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($method == Methods::POST) {

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        if (!is_array(json_decode($server_output, true)))
            var_dump("Error: " . $server_output);
        return ($server_output);
    }

    /**
     * @return mixed
     */
    public function getHttpClient()
    {
        return $this->httpClient;
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