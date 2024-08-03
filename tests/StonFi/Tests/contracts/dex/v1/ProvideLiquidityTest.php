<?php

namespace StonFi\Tests\contracts\dex\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use StonFi\const\v1\gas\provide\LpJettonGas;
use StonFi\const\v1\gas\provide\LpTonGas;
use StonFi\const\v1\gas\swap\JettonToJettonGas;
use StonFi\const\v1\gas\swap\JettonToTonGas;
use StonFi\const\v1\gas\swap\TonToJettonGas;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\dex\v1\ProvideLiquidity;
use PHPUnit\Framework\TestCase;
use StonFi\enums\Networks;
use StonFi\Init;
use StonFi\pTON\v1\PtonV1;


const OFFER_JETTON_ADDRESS = "EQA2kCVNwVsil2EM2mB0SkXytxCqQjS4mttjDpnXmwG9T6bO"; // STON
const ASK_JETTON_ADDRESS = "EQBX6K9aXVl3nXINCyPPL86C4ONVmQ8vK360u6dykFKXpHCa"; // GEMSTON
const USER_WALLET_ADDRESS = "UQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaEAn";

class ProvideLiquidityTest extends TestCase
{
    public Init $init;
    public ProvideLiquidity $provideLiquidity;

    protected function setUp(): void
    {
        $this->init = new Init(Networks::MAINNET);
        $this->provideLiquidity = new ProvideLiquidity($this->init);

        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testGas()
    {
        $this->assertEquals("300000000", (new LpJettonGas())->gasAmount, 'LpJettonGas');
        $this->assertEquals("260000000", (new LpTonGas())->forwardGasAmount, 'LpTonGas');
    }

    public function testCreateProvideLiquidityBody()
    {
        $routerWalletAddress = new Address("EQAIBnMGyR4vXuaF3OzR80LIZ2Z_pe3z-_t_q6Blu2HKLeaY");
        $minLpOut = BigInteger::of(900000000);

        $result = $this->provideLiquidity->createProvideLiquidityBody($routerWalletAddress, $minLpOut);
        $this->assertEquals(
            "te6cckEBAQEALAAAU/z55Y+AAQDOYNkjxevc0Ludmj5oWQzsz/S9vn9/b/V0DLdsOUWoa0nSAdDW3xU=",
            Bytes::bytesToBase64($result->toBoc(false))
        );
    }

    public function testJettonTxParams() // todo
    {
        $userWalletAddress = new Address(USER_WALLET_ADDRESS);
        $sendTokenAddress = new Address(OFFER_JETTON_ADDRESS);
        $otherTokenAddress = new Address(ASK_JETTON_ADDRESS);
        $sendAmount = BigInteger::of("500000000");
        $minLpOut = BigInteger::of("1");

        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("getWalletAddress")
            ->willReturnCallback(function ($arg0, $arg1) {
                if ((new Address($arg1))->isEqual(new Address(OFFER_JETTON_ADDRESS)))
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOACD+9EGh6wT/2pEbZWrfCmVbsdpQVGU9308qh2gel9QwQM97q5A==", true)->beginParse()->loadAddress();
                if ((new Address($arg1))->isEqual(new Address(ASK_JETTON_ADDRESS)))
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOAAQDOYNkjxevc0Ludmj5oWQzsz/S9vn9/b/V0DLdsOUWw40LsPA==", true)->beginParse()->loadAddress();
            });

        $this->provideLiquidity = new ProvideLiquidity($this->init, CallContractMethods: $mock);

        // TEST 1 - should build expected tx params
        $result = $this->provideLiquidity->JettonTxParams(
            userWalletAddress: $userWalletAddress,
            sendTokenAddress: $sendTokenAddress,
            otherTokenAddress: $otherTokenAddress,
            sendAmount: $sendAmount,
            minLpOut: $minLpOut
        );

        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
        );
        $this->assertEquals(
            "te6cckEBAgEAhAABsA+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCBycOAEBAE38+eWPgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFogMsmgJ2",
            $result->payload,
        );
        $this->assertEquals(
            BigInteger::of(300000000),
            $result->value
        );


        // TEST 2 - should build expected tx params when queryId is defined
        $result = $this->provideLiquidity->JettonTxParams(
            userWalletAddress: $userWalletAddress,
            sendTokenAddress: $sendTokenAddress,
            otherTokenAddress: $otherTokenAddress,
            sendAmount: $sendAmount,
            minLpOut: $minLpOut,
            queryId: 12345
        );

        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
        );
        $this->assertEquals(
            "te6cckEBAgEAhAABsA+KfqUAAAAAAAAwOUHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCBycOAEBAE38+eWPgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFogOYpqed",
            $result->payload,
        );
        $this->assertEquals(
            BigInteger::of(300000000),
            $result->value
        );

        // TEST 3 - should build expected tx params when queryId is defined
        $result = $this->provideLiquidity->JettonTxParams(
            userWalletAddress: $userWalletAddress,
            sendTokenAddress: $sendTokenAddress,
            otherTokenAddress: $otherTokenAddress,
            sendAmount: $sendAmount,
            minLpOut: $minLpOut,
            gasAmount: BigInteger::of("1"),
            forwardGasAmount: BigInteger::of("2"),
        );

        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
        );
        $this->assertEquals(
            "te6cckEBAgEAgQABqg+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaAgUBAE38+eWPgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFogPlXGF2",
            $result->payload,
        );
        $this->assertEquals(
            BigInteger::of(1),
            $result->value
        );
    }

    public function testTonTxParams()
    {
        $userWalletAddress = new Address(USER_WALLET_ADDRESS);
        $otherTokenAddress = new Address(ASK_JETTON_ADDRESS);
        $proxyTon = new PtonV1($this->init);
        $sendAmount = BigInteger::of("500000000");
        $minLpOut = BigInteger::of("1");

        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("getWalletAddress")
            ->willReturnCallback(function ($arg0, $arg1) {
                if ((new Address($arg1))->isEqual((new PtonV1($this->init))->address))
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOAAioWoxZMTVqjEz8xEP8QSW4AyorIq+/8UCfgJNM0gMPwJB4oTQ==", true)->beginParse()->loadAddress();
                if ((new Address($arg1))->isEqual(new Address(ASK_JETTON_ADDRESS))) {
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOAAQDOYNkjxevc0Ludmj5oWQzsz/S9vn9/b/V0DLdsOUWw40LsPA==", true)->beginParse()->loadAddress();
                }
            });

        $this->provideLiquidity = new ProvideLiquidity($this->init, CallContractMethods: $mock);

        // TEST 1 - should build expected tx params
        $result = $this->provideLiquidity->TonTxParams(
            userWalletAddress: $userWalletAddress,
            proxyTon: $proxyTon,
            otherTokenAddress: $otherTokenAddress,
            sendAmount: $sendAmount,
            minLpOut: $minLpOut,
        );

        $this->assertEquals(
            $result->address->isEqual(new Address("EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC")),
            true
        );
        $this->assertEquals(
            "te6cckEBAgEAYwABbQ+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcED39JAMBAE38+eWPgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFogPLyEMA",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of("760000000"),
            $result->value
        );


        // TEST 2 - should build expected tx params when queryId is defined
        $result = $this->provideLiquidity->TonTxParams(
            userWalletAddress: $userWalletAddress,
            proxyTon: $proxyTon,
            otherTokenAddress: $otherTokenAddress,
            sendAmount: $sendAmount,
            minLpOut: $minLpOut,
            queryId: 12345
        );

        $this->assertEquals(
            $result->address->isEqual(new Address("EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC")),
            true
        );
        $this->assertEquals(
            "te6cckEBAgEAYwABbQ+KfqUAAAAAAAAwOUHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcED39JAMBAE38+eWPgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFogOsHyfF",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of("760000000"),
            $result->value
        );


        // TEST 3 - should build expected tx params when custom gasAmount is defined
        $result = $this->provideLiquidity->TonTxParams(
            userWalletAddress: $userWalletAddress,
            proxyTon: $proxyTon,
            otherTokenAddress: $otherTokenAddress,
            sendAmount: $sendAmount,
            minLpOut: $minLpOut,
            forwardGasAmount: BigInteger::of(2)
        );

        $this->assertEquals(
            $result->address->isEqual(new Address("EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC")),
            true
        );
        $this->assertEquals(
            "te6cckEBAgEAYAABZw+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcBAsBAE38+eWPgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFogMUO5pP",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of("500000002"),
            $result->value
        );
    }
}
