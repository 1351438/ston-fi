<?php

namespace StonFi\Tests\contracts\dex\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use StonFi\const\v1\gas\pool\BurnGas;
use StonFi\const\v1\gas\pool\CollectFeeGas;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\dex\v1\Pool;
use PHPUnit\Framework\TestCase;
use StonFi\contracts\dex\v1\ProvideLiquidity;
use StonFi\enums\Networks;
use StonFi\Init;
use StonFi\pTON\v1\PtonV1;

const POOL_ADDRESS = "EQCl-ax-5QC06ub096s2bqTbhYZWigZ1-FkJm2IVIMSazp7U"; // STON/GEMSTON pool
const USER_WALLET_ADDRESS = "UQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaEAn";

class PoolTest extends TestCase
{
    public Init $init;
    public Pool $pool;

    protected function setUp(): void
    {
        $this->init = new Init(Networks::MAINNET);
        $this->pool = new Pool($this->init, POOL_ADDRESS);
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testGas()
    {
        $this->assertEquals(("500000000"), (new BurnGas())->gasAmount);
        $this->assertEquals(("1100000000"), (new CollectFeeGas())->gasAmount);
    }

    public function testGetPoolAddress()
    {
        $poolAddr = $this->pool->getPoolAddress("0:87b92241aa6a57df31271460c109c54dfd989a1aea032f6107d2c65d6e8879ce", "0:9f557c3e09518b8a73bccfef561896832a35b220e85df1f66834b2170db0dfcb");
        $this->assertEquals(
            $poolAddr->isEqual(new Address("EQCl-ax-5QC06ub096s2bqTbhYZWigZ1-FkJm2IVIMSazp7U")),
            true
        );
    }

    public function testCreateCollectFeesBody()
    {
        //Test 1
        $res = $this->pool->createCollectFeeBody();
        $this->assertEquals("te6cckEBAQEADgAAGB/LfT0AAAAAAAAAAOHc0mQ=", Bytes::bytesToBase64($res->toBoc(false)));

        // Test 2 - should build expected tx body when queryId is defined
        $res = $this->pool->createCollectFeeBody(12345);
        $this->assertEquals("te6cckEBAQEADgAAGB/LfT0AAAAAAAAwOR9czWw=", Bytes::bytesToBase64($res->toBoc(false)));
    }


    public function testGetCollectFeeTxParams()
    {
        // TEST 1
        $result = $this->pool->getCollectFeeTxParams();
        $this->assertEquals(
            $result->address->isEqual(new Address("EQCl-ax-5QC06ub096s2bqTbhYZWigZ1-FkJm2IVIMSazp7U")),
            true
        );
        $this->assertEquals(
            "te6cckEBAQEADgAAGB/LfT0AAAAAAAAAAOHc0mQ=",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(1100000000),
            $result->value
        );

        // TEST 2
        $result = $this->pool->getCollectFeeTxParams(queryId: 12345);
        $this->assertEquals(
            $result->address->isEqual(new Address("EQCl-ax-5QC06ub096s2bqTbhYZWigZ1-FkJm2IVIMSazp7U")),
            true
        );
        $this->assertEquals(
            "te6cckEBAQEADgAAGB/LfT0AAAAAAAAwOR9czWw=",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(1100000000),
            $result->value
        );

        // TEST 3
        $result = $this->pool->getCollectFeeTxParams(gasAmount: BigInteger::of(1));
        $this->assertEquals(
            $result->address->isEqual(new Address("EQCl-ax-5QC06ub096s2bqTbhYZWigZ1-FkJm2IVIMSazp7U")),
            true
        );
        $this->assertEquals(
            "te6cckEBAQEADgAAGB/LfT0AAAAAAAAAAOHc0mQ=",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(1),
            $result->value
        );
    }

    public function testCreateBurnBody()
    {
        $amount = BigInteger::of(1000000000);
        $responseAddress = new Address(USER_WALLET_ADDRESS);

        // TEST 1
        $result = $this->pool->createBurnBody($amount, $responseAddress);
        $this->assertEquals(
            "te6cckEBAQEANAAAY1lfB7wAAAAAAAAAAEO5rKAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRhaz3TA==",
            Bytes::bytesToBase64($result->toBoc(false))
        );


        // TEST 2
        $result = $this->pool->createBurnBody($amount, $responseAddress, 12345);
        $this->assertEquals(
            "te6cckEBAQEANAAAY1lfB7wAAAAAAAAwOUO5rKAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRGnDxRA==",
            Bytes::bytesToBase64($result->toBoc(false))
        );
    }

    public function testGetBurnTxParams()
    {
        $amount = BigInteger::of(1000000000);
        $responseAddress = new Address(USER_WALLET_ADDRESS);

        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("getWalletAddress")
            ->willReturnCallback(function ($arg0, $arg1) {
                if ((new Address($arg0))->isEqual(POOL_ADDRESS))
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOACstDZ3ATHWF//MUN1iK/rfVwlHFuhUxxdp3sB2jMtipQs2Cj5Q==", true)->beginParse()->loadAddress();
                throw new \Exception("Inputs are wrong");
            });

        $this->pool = new Pool($this->init, POOL_ADDRESS, CallContractMethods: $mock);


        // TEST 1 - should build expected tx params
        $result = $this->pool->getBurnTxParams(
            $amount,
            $responseAddress
        );
        $this->assertEquals(
            $result->address->isEqual(new Address("EQBWWhs7gJjrC__mKG6xFf1vq4Sji3QqY4u072A7RmWxUoT1")),
            true
        );
        $this->assertEquals(
            "te6cckEBAQEANAAAY1lfB7wAAAAAAAAAAEO5rKAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRhaz3TA==",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(500000000),
            $result->value
        );

        // TEST 2 - should build expected tx params when queryId is defined
        $result = $this->pool->getBurnTxParams(
            $amount,
            $responseAddress,
            queryId: 12345
        );
        $this->assertEquals(
            $result->address->isEqual(new Address("EQBWWhs7gJjrC__mKG6xFf1vq4Sji3QqY4u072A7RmWxUoT1")),
            true
        );
        $this->assertEquals(
            "te6cckEBAQEANAAAY1lfB7wAAAAAAAAwOUO5rKAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRGnDxRA==",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(500000000),
            $result->value
        );

        // TEST 3 - should build expected tx params when custom gasAmount is defined
        $result = $this->pool->getBurnTxParams(
            $amount,
            $responseAddress,
            gasAmount: BigInteger::of(1)
        );
        $this->assertEquals(
            $result->address->isEqual(new Address("EQBWWhs7gJjrC__mKG6xFf1vq4Sji3QqY4u072A7RmWxUoT1")),
            true
        );
        $this->assertEquals(
            "te6cckEBAQEANAAAY1lfB7wAAAAAAAAAAEO5rKAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRhaz3TA==",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(1),
            $result->value
        );
    }

    public function testGetExpectedOutputs()
    {
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("runMethod")
            ->willReturnCallback(function ($contractAddress, $method, $arg2) {
                return json_encode([
                    'success' => true,
                    'stack' => [
                        [
                            'type' => 'num',
                            'num' => dechex(78555061853)
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex(78633696)
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex(0)
                        ],
                    ]
                ]);
            });

        $this->pool = new Pool($this->init, POOL_ADDRESS, CallContractMethods: $mock);

        // TEST 1 - should make on-chain request and return parsed response
        $result = $this->pool->getExpectedOutputs(
            amount: BigInteger::of(1000000000),
            jettonWallet: "0:87b92241aa6a57df31271460c109c54dfd989a1aea032f6107d2c65d6e8879ce"
        );

        $this->assertEquals(
            BigInteger::of(78555061853),
            $result->jettonToReceive
        );
        $this->assertEquals(
            BigInteger::of(78633696),
            $result->protocolFeePaid
        );
        $this->assertEquals(
            BigInteger::of(0),
            $result->refFeePaid
        );
    }

    public function testGetExpectedTokens()
    {

        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("runMethod")
            ->willReturnCallback(function ($contractAddress, $method, $arg2) {
                return json_encode([
                    'success' => true,
                    'stack' => [
                        [
                            'type' => 'num',
                            'num' => dechex(19)
                        ],
                    ]
                ]);
            });

        $this->pool = new Pool($this->init, POOL_ADDRESS, CallContractMethods: $mock);

        // TEST 1 - should make on-chain request and return parsed response
        $result = $this->pool->getExpectedTokens(
            BigInteger::of(100000),
            BigInteger::of(200000),
        );
        $this->assertEquals(BigInteger::of(19), $result);
    }

    public function testGetExpectedLiquidity()
    {
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("runMethod")
            ->willReturnCallback(function ($contractAddress, $method, $arg2) {
                return json_encode([
                    'success' => true,
                    'stack' => [
                        [
                            'type' => 'num',
                            'num' => dechex(128)
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex(10128)
                        ],
                    ]
                ]);
            });

        $this->pool = new Pool($this->init, POOL_ADDRESS, CallContractMethods: $mock);

        // TEST 1
        $result = $this->pool->getExpectedLiquidity(
            BigInteger::of(1),
        );

        $this->assertEquals(BigInteger::of(128), $result->amount0);
        $this->assertEquals(BigInteger::of(10128), $result->amount1);
    }

    public function testGetLpAccountAddress()
    {
        $ownerAddress = new Address("UQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaEAn");
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("runMethod")
            ->willReturnCallback(function ($contractAddress, $method, $arg2) {
                return json_encode([
                    'success' => true,
                    'stack' => [
                        [
                            'type' => 'slice',
                            'slice' => Bytes::bytesToHexString(Bytes::base64ToBytes("te6ccsEBAQEAJAAAAEOAH6VkyTu5g20HTCbGu1/OTfiBSIoSdeKMzW42GndI90LQv9OlMw=="))
                        ],
                    ]
                ]);
            });

        // TEST 1
        $this->pool = new Pool($this->init, POOL_ADDRESS, CallContractMethods: $mock);

        $res = $this->pool->getLpAccountAddress(
            $ownerAddress->toString()
        );

        $this->assertEquals($res->isEqual(new Address("EQD9KyZJ3cwbaDphNjXa_nJvxApEUJOvFGZrcbDTuke6Fs7B")), true);
    }

    public function testGetPoolData()
    {
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("runMethod")
            ->willReturnCallback(function ($contractAddress, $method, $arg2) {
                return json_encode([
                    'success' => true,
                    'stack' => [
                        [
                            'type' => 'num',
                            'num' => dechex('14659241047997')
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex('1155098971931369')
                        ],
                        [
                            'type' => 'slice',
                            'slice' => Bytes::bytesToHexString(Bytes::base64ToBytes("te6ccsEBAQEAJAAAAEOAEPckSDVNSvvmJOKMGCE4qb+zE0NdQGXsIPpYy63RDznQToI/Aw=="))
                        ],
                        [
                            'type' => 'slice',
                            'slice' => Bytes::bytesToHexString(Bytes::base64ToBytes("te6ccsEBAQEAJAAAAEOAE+qvh8EqMXFOd5n96sMS0GVGtkQdC74+zQaWQuG2G/lwYlQIaw=="))
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex('20')
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex('10')
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex('10')
                        ],
                        [
                            'type' => 'slice',
                            'slice' => Bytes::bytesToHexString(Bytes::base64ToBytes("te6ccsEBAQEAJAAAAEOAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQvWZ7LQ=="))
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex('71678297602')
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex('3371928931127')
                        ],
                    ]
                ]);
            });

        $this->pool = new Pool($this->init, POOL_ADDRESS, CallContractMethods: $mock);

        // TEST 1
        $result = $this->pool->getPoolData();
        $this->assertEquals(
            BigInteger::of('14659241047997'),
            $result->reserve0
        );
        $this->assertEquals(
            BigInteger::of('1155098971931369'),
            $result->reserve1
        );
        $this->assertTrue(
            $result->token0WalletAddress->isEqual(new Address("EQCHuSJBqmpX3zEnFGDBCcVN_ZiaGuoDL2EH0sZdboh5zkwy")),
        );
        $this->assertTrue(
            $result->token1WalletAddress->isEqual(new Address("EQCfVXw-CVGLinO8z-9WGJaDKjWyIOhd8fZoNLIXDbDfy2kw")),
        );
        $this->assertEquals(
            BigInteger::of('20'),
            $result->lpFee
        );
        $this->assertEquals(
            BigInteger::of('10'),
            $result->protocolFee
        );
        $this->assertEquals(
            BigInteger::of('10'),
            $result->refFee
        );
        $this->assertTrue(
            $result->protocolFeeAddress->isEqual(new Address("EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c")),
        );
        $this->assertEquals(
            BigInteger::of('71678297602'),
            $result->collectedToken0ProtocolFee
        );
        $this->assertEquals(
            BigInteger::of('3371928931127'),
            $result->collectedToken1ProtocolFee
        );
    }
}
