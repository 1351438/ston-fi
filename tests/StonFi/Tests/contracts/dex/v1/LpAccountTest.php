<?php

namespace StonFi\Tests\contracts\dex\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Bytes;
use StonFi\const\v1\gas\lp_account\DirectAddLpGas;
use StonFi\const\v1\gas\lp_account\RefundGas;
use StonFi\const\v1\gas\lp_account\ResetGas;
use StonFi\const\v1\models\LpAccountData;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\dex\v1\LpAccount;
use PHPUnit\Framework\TestCase;
use StonFi\contracts\dex\v1\Pool;
use StonFi\enums\Networks;
use StonFi\Init;

const LP_ACCOUNT_ADDRESS = "EQD9KyZJ3cwbaDphNjXa_nJvxApEUJOvFGZrcbDTuke6Fs7B"; // LP account of `UQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaEAn` wallet for STON/GEMSTON pool

class LpAccountTest extends TestCase
{

    public Init $init;
    public LpAccount $lpAccount;

    protected function setUp(): void
    {
        $this->init = new Init(Networks::MAINNET);
        $this->lpAccount = new LpAccount($this->init, new Address(LP_ACCOUNT_ADDRESS));
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testGas()
    {
        $this->assertEquals("300000000", (new DirectAddLpGas())->gasAmount);
        $this->assertEquals("300000000", (new RefundGas())->gasAmount);
        $this->assertEquals("300000000", (new ResetGas())->gasAmount);
    }

    public function testCreateRefundBody()
    {
        // TEST 1
        $body = $this->lpAccount->createRefundBody();
        $this->assertEquals("te6cckEBAQEADgAAGAvz9EcAAAAAAAAAANCoHB4=", Bytes::bytesToBase64($body->toBoc(false)));


        // TEST 2
        $body = $this->lpAccount->createRefundBody(12345);
        $this->assertEquals("te6cckEBAQEADgAAGAvz9EcAAAAAAAAwOS4oAxY=", Bytes::bytesToBase64($body->toBoc(false)));
    }

    public function testGetRefundTxParams()
    {
        // TEST 1 - should build expected tx params
        $result = $this->lpAccount->getRefundTxParams();
        $this->assertTrue($result->address->isEqual(new Address(LP_ACCOUNT_ADDRESS)));
        $this->assertEquals("te6cckEBAQEADgAAGAvz9EcAAAAAAAAAANCoHB4=", $result->payload);
        $this->assertEquals((new RefundGas())->gasAmount, $result->value);


        // TEST 2 - should build expected tx params when queryId is defined
        $result = $this->lpAccount->getRefundTxParams(queryId: 12345);
        $this->assertTrue($result->address->isEqual(new Address(LP_ACCOUNT_ADDRESS)));
        $this->assertEquals("te6cckEBAQEADgAAGAvz9EcAAAAAAAAwOS4oAxY=", $result->payload);
        $this->assertEquals((new RefundGas())->gasAmount, $result->value);


        // TEST 3 - should build expected tx params when custom gasAmount is defined
        $result = $this->lpAccount->getRefundTxParams(gasAmount: BigInteger::of(1));
        $this->assertTrue($result->address->isEqual(new Address(LP_ACCOUNT_ADDRESS)));
        $this->assertEquals("te6cckEBAQEADgAAGAvz9EcAAAAAAAAAANCoHB4=", $result->payload);
        $this->assertEquals(BigInteger::of(1), $result->value);
    }

    public function testCreateDirectAddLiquidityBody()
    {
        $amount0 = BigInteger::of("1000000000");
        $amount1 = BigInteger::of("2000000000");

        // TEST 1
        $body = $this->lpAccount->createDirectAddLiquidityBody($amount0, $amount1);

        $this->assertEquals(
            "te6cckEBAQEAGQAALUz4KAMAAAAAAAAAAEO5rKAEdzWUABAYcHfdXg==",
            Bytes::bytesToBase64($body->toBoc(false))
        );


        // TEST 2
        $body = $this->lpAccount->createDirectAddLiquidityBody($amount0, $amount1, queryId: 12345);

        $this->assertEquals(
            "te6cckEBAQEAGQAALUz4KAMAAAAAAAAwOUO5rKAEdzWUABAYwTuNzw==",
            Bytes::bytesToBase64($body->toBoc(false))
        );

        // TEST 3
        $body = $this->lpAccount->createDirectAddLiquidityBody($amount0, $amount1, minimumLpToMint: BigInteger::of(300));

        $this->assertEquals(
            "te6cckEBAQEAGgAAL0z4KAMAAAAAAAAAAEO5rKAEdzWUACASyH+t4/4=",
            Bytes::bytesToBase64($body->toBoc(false))
        );
    }

    public function testGetDirectAddLiquidityTxParams()
    {
        $amount0 = BigInteger::of("1");
        $amount1 = BigInteger::of("2");

        // TEST 1
        $result = $this->lpAccount->getDirectAddLiquidityTxParams($amount0, $amount1);
        $this->assertTrue($result->address->isEqual(new Address(LP_ACCOUNT_ADDRESS)));
        $this->assertEquals("te6cckEBAQEAEwAAIUz4KAMAAAAAAAAAABARAhAYcJq3kQ==", $result->payload);
        $this->assertEquals((new DirectAddLpGas())->gasAmount, $result->value);


        // TEST 2
        $result = $this->lpAccount->getDirectAddLiquidityTxParams($amount0, $amount1, queryId: 12345);
        $this->assertTrue($result->address->isEqual(new Address(LP_ACCOUNT_ADDRESS)));
        $this->assertEquals("te6cckEBAQEAEwAAIUz4KAMAAAAAAAAwORARAhAY3rZ5+g==", $result->payload);
        $this->assertEquals((new DirectAddLpGas())->gasAmount, $result->value);

        // TEST 3
        $result = $this->lpAccount->getDirectAddLiquidityTxParams($amount0, $amount1, minimumLpToMint: BigInteger::of(3));
        $this->assertTrue($result->address->isEqual(new Address(LP_ACCOUNT_ADDRESS)));
        $this->assertEquals("te6cckEBAQEAEwAAIUz4KAMAAAAAAAAAABARAhA4rhQKsQ==", $result->payload);
        $this->assertEquals((new DirectAddLpGas())->gasAmount, $result->value);

    }

    public function testCreateResetGasBody()
    {
        // TEST 1
        $body = $this->lpAccount->createResetGasBody();
        $this->assertEquals(
            "te6cckEBAQEADgAAGEKg+0MAAAAAAAAAAPc9hrQ=",
            Bytes::bytesToBase64($body->toBoc(false))
        );

        // TEST 2
        $body = $this->lpAccount->createResetGasBody(queryId: 12345);
        $this->assertEquals(
            "te6cckEBAQEADgAAGEKg+0MAAAAAAAAwOQm9mbw=",
            Bytes::bytesToBase64($body->toBoc(false))
        );
    }

    public function testGetResetGasTxParams()
    {
        // TEST 1
        $result = $this->lpAccount->getResetGasTxParams();
        $this->assertTrue($result->address->isEqual(new Address(LP_ACCOUNT_ADDRESS)));
        $this->assertEquals("te6cckEBAQEADgAAGEKg+0MAAAAAAAAAAPc9hrQ=", $result->payload);
        $this->assertEquals((new ResetGas())->gasAmount, $result->value);


        // TEST 2
        $result = $this->lpAccount->getResetGasTxParams(queryId: 12345);
        $this->assertTrue($result->address->isEqual(new Address(LP_ACCOUNT_ADDRESS)));
        $this->assertEquals("te6cckEBAQEADgAAGEKg+0MAAAAAAAAwOQm9mbw=", $result->payload);
        $this->assertEquals((new ResetGas())->gasAmount, $result->value);
    }

    public function testGetLpAccountData()
    {
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("call")
            ->willReturnCallback(function ($contractAddress, $method, $arg2) {
                return json_encode([
                    'success' => true,
                    'stack' => [
                        [
                            'type' => 'slice',
                            'slice' => Bytes::bytesToHexString(Bytes::base64ToBytes("te6ccsEBAQEAJAAAAEOAAhPiXVKvsD1hxGhnnmesB49ksyqyDn0xoH/5PQAiWc0QY+4g6g=="))
                        ],
                        [
                            'type' => 'slice',
                            'slice' => Bytes::bytesToHexString(Bytes::base64ToBytes("te6ccsEBAQEAJAAAAEOAFL81j9ygFp1c3p71Zs3Um3CwytFAzr8LITNsQqQYk1nQDFEwYA=="))
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex(0)
                        ],
                        [
                            'type' => 'num',
                            'num' => dechex(0)
                        ],
                    ]
                ]);
            });

        $this->lpAccount = new LpAccount($this->init, new Address(LP_ACCOUNT_ADDRESS), CallContractMethods: $mock);

        $result = $this->lpAccount->getLpAccountData();
        $this->assertTrue($result->userAddress->isEqual(new Address("EQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaB3i")));
        $this->assertTrue($result->poolAddress->isEqual(new Address("EQCl-ax-5QC06ub096s2bqTbhYZWigZ1-FkJm2IVIMSazp7U")));
        $this->assertEquals("0", $result->amount0);
        $this->assertEquals("0", $result->amount1);

    }
}
