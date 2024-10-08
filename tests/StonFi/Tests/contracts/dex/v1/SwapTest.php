<?php

namespace StonFi\Tests\contracts\dex\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Bytes;
use Olifanton\Interop\Units;
use StonFi\const\v1\gas\provide\LpJettonGas;
use StonFi\const\v1\gas\provide\LpTonGas;
use StonFi\const\v1\gas\swap\JettonToJettonGas;
use StonFi\const\v1\gas\swap\JettonToTonGas;
use StonFi\const\v1\gas\swap\TonToJettonGas;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\dex\v1\Swap;
use PHPUnit\Framework\TestCase;
use StonFi\enums\Networks;
use StonFi\Init;
use StonFi\pTON\v1\PtonV1;

const OFFER_JETTON_ADDRESS = "EQA2kCVNwVsil2EM2mB0SkXytxCqQjS4mttjDpnXmwG9T6bO"; // STON
const ASK_JETTON_ADDRESS = "EQBX6K9aXVl3nXINCyPPL86C4ONVmQ8vK360u6dykFKXpHCa"; // GEMSTON
const USER_WALLET_ADDRESS = "UQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaEAn";

class SwapTest extends TestCase
{
    private $init, $swap;

    protected function setUp(): void
    {
        $this->init = new Init(Networks::MAINNET);
        $this->swap = new Swap($this->init);

        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testGas()
    {
        $this->assertEquals("300000000", (new LpJettonGas())->gasAmount, 'LpJettonGas');
        $this->assertEquals("260000000", (new LpTonGas())->forwardGasAmount, 'LpTonGas');
        $this->assertEquals("175000000", (new JettonToJettonGas())->forwardGasAmount, 'JettonToJettonGas');
        $this->assertEquals("220000000", (new JettonToJettonGas())->gasAmount, 'JettonToJettonGas');
        $this->assertEquals("125000000", (new JettonToTonGas())->forwardGasAmount, 'JettonToTonGas');
        $this->assertEquals("170000000", (new JettonToTonGas())->gasAmount, 'JettonToTonGas');
        $this->assertEquals("185000000", (new TonToJettonGas())->forwardGasAmount, 'TonToJettonGas');
    }

    public function testGetRouterAddress()
    {
        $this->assertEquals('EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt', $this->swap->getRouterAddress());
    }

    public function testCreateSwapBody()
    {
        // Test 1 - should build expected tx body
        $test1 = $this->swap->createSwapBody(
            userWalletAddress: new Address(USER_WALLET_ADDRESS),
            askJettonWalletAddress: new Address(ASK_JETTON_ADDRESS),
            minAskAmount: Units::toNano("0.9"));

        $this->assertEquals(
            "te6cckEBAQEATgAAlyWThWGACv0V60urLvOuQaFkeeX50FwcarMh5eVv1pd07lIKUvSIa0nSAQAEJ8S6pV9gesOI0M88z1gPHslmVWQc+mNA//J6AESzmhCqzILs",
            Bytes::bytesToBase64($test1->toBoc(false)),
            'should build expected tx body'
        );

        // Test 2 - should build expected tx body when referralAddress is defined
        $test2 = $this->swap->createSwapBody(
            userWalletAddress: new Address(USER_WALLET_ADDRESS),
            askJettonWalletAddress: new Address(ASK_JETTON_ADDRESS),
            minAskAmount: Units::toNano("0.9"),
            referralAddress: new Address(USER_WALLET_ADDRESS));

        $this->assertEquals(
            "te6cckEBAQEAbwAA2SWThWGACv0V60urLvOuQaFkeeX50FwcarMh5eVv1pd07lIKUvSIa0nSAQAEJ8S6pV9gesOI0M88z1gPHslmVWQc+mNA//J6AESzmjAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaKezq+h",
            Bytes::bytesToBase64($test2->toBoc(false)),
            'should build expected tx body when referralAddress is defined'
        );
    }

    public function testJettonToJettonTxParams()
    {
        $userWalletAddress = new Address(USER_WALLET_ADDRESS);
        $offerJettonAddress = new Address(OFFER_JETTON_ADDRESS);
        $askJettonAddress = new Address(ASK_JETTON_ADDRESS);
        $offerAmount = BigInteger::of("500000000");
        $askAmount = BigInteger::of("200000000");
        $referralAddress = new Address(USER_WALLET_ADDRESS);
        $queryId = 12345;

        $routerAddress = $this->init->getRouter();
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("getWalletAddress")
            ->willReturnCallback(function ($arg0, $arg1) {

                $offerJettonAddress = new Address(OFFER_JETTON_ADDRESS);
                $askJettonAddress = new Address(ASK_JETTON_ADDRESS);

                if (new Address($arg1) == $askJettonAddress)
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOAAQDOYNkjxevc0Ludmj5oWQzsz/S9vn9/b/V0DLdsOUWw40LsPA==", true)->beginParse()->loadAddress();
                if (new Address($arg1) == $offerJettonAddress)
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOACD+9EGh6wT/2pEbZWrfCmVbsdpQVGU9308qh2gel9QwQM97q5A==", true)->beginParse()->loadAddress();
            });


        $this->init = new Init(Networks::MAINNET);
        $this->swap = new Swap($this->init, callContractMethod: $mock);

        // Test1
        $result = $this->swap->JettonToJettonTxParams(
            userWalletAddress: $userWalletAddress,
            offerJettonAddress: $offerJettonAddress,
            askJettonAddress: $askJettonAddress,
            offerAmount: $offerAmount,
            minAskAmount: $askAmount
        );


        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Receiver address (to)");

        $this->assertEquals(
            "te6cckEBAgEAqQABsA+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCBTck4EBAJclk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQsE7cRQ==",
            $result->payload,
            'Payload assertion'
        );
        $this->assertEquals(
            BigInteger::of(220000000),
            $result->value,
            'Payload assertion'
        );


        // Test2
        $result = $this->swap->JettonToJettonTxParams(
            userWalletAddress: $userWalletAddress,
            offerJettonAddress: $offerJettonAddress,
            askJettonAddress: $askJettonAddress,
            offerAmount: $offerAmount,
            minAskAmount: $askAmount,
            referralAddress: $referralAddress
        );

        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Receiver address (to)");

        $this->assertEquals(
            "te6cckEBAgEAygABsA+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCBTck4EBANklk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5owAEJ8S6pV9gesOI0M88z1gPHslmVWQc+mNA//J6AESzmiwUoOyQ==",
            $result->payload,
            'Payload assertion'
        );
        $this->assertEquals(
            BigInteger::of(220000000),
            $result->value,
            'Payload assertion'
        );

        // Test3
        $result = $this->swap->JettonToJettonTxParams(
            userWalletAddress: $userWalletAddress,
            offerJettonAddress: $offerJettonAddress,
            askJettonAddress: $askJettonAddress,
            offerAmount: $offerAmount,
            minAskAmount: $askAmount,
            queryId: $queryId
        );

        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Receiver address (to)");

        $this->assertEquals(
            "te6cckEBAgEAqQABsA+KfqUAAAAAAAAwOUHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCBTck4EBAJclk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQ/ZI/NA==",
            $result->payload,
            'Payload assertion'
        );
        $this->assertEquals(
            BigInteger::of(220000000),
            $result->value,
            'Payload assertion'
        );


        // Test4
        $result = $this->swap->JettonToJettonTxParams(
            userWalletAddress: $userWalletAddress,
            offerJettonAddress: $offerJettonAddress,
            askJettonAddress: $askJettonAddress,
            offerAmount: $offerAmount,
            minAskAmount: $askAmount,
            gasAmount: BigInteger::of('1')
        );

        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Receiver address (to)");

        $this->assertEquals(
            "te6cckEBAgEAqQABsA+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCBTck4EBAJclk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQsE7cRQ==",
            $result->payload,
            'Payload assertion'
        );
        $this->assertEquals(
            BigInteger::of(1),
            $result->value,
            'Payload assertion'
        );
    }

    public function testJettonToTonTxParams()
    {
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("getWalletAddress")
            ->willReturnCallback(function ($arg0, $arg1) {
                $offerJettonAddress = new Address(OFFER_JETTON_ADDRESS);

                if (new Address($arg1) == $offerJettonAddress)
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOACD+9EGh6wT/2pEbZWrfCmVbsdpQVGU9308qh2gel9QwQM97q5A==", true)->beginParse()->loadAddress();
                if (new Address($arg1) == (new PtonV1($this->init))->address)
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOAAioWoxZMTVqjEz8xEP8QSW4AyorIq+/8UCfgJNM0gMPwJB4oTQ==", true)->beginParse()->loadAddress();
            });


        $userWalletAddress = new Address(USER_WALLET_ADDRESS);
        $offerJettonAddress = new Address(OFFER_JETTON_ADDRESS);
        $proxyTon = new PtonV1($this->init, provider: $mock);
        $offerAmount = BigInteger::of("500000000");
        $askAmount = BigInteger::of("200000000");
        $referralAddress = new Address(USER_WALLET_ADDRESS);
        $queryId = 12345;

        $this->swap = new Swap($this->init, callContractMethod: $mock);

        // Test 1 - should build expected tx params
        $result = $this->swap->JettonToTonTxParams(
            $proxyTon,
            $userWalletAddress,
            $offerJettonAddress,
            $offerAmount,
            $askAmount,
        );
        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Address Assertion"
        );
        $this->assertEquals(
            "te6cckEBAgEAqQABsA+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCA7msoEBAJclk4VhgAIqFqMWTE1aoxM/MRD/EEluAMqKyKvv/FAn4CTTNIDD6BfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQv9h8dw==",
            $result->payload,
            "Payload Assertion"
        );
        $this->assertEquals(BigInteger::of('170000000'), $result->value, "Amount Assertion");

        // Test 2 - should build expected tx params when referralAddress is defined
        $result = $this->swap->JettonToTonTxParams(
            $proxyTon,
            $userWalletAddress,
            $offerJettonAddress,
            $offerAmount,
            $askAmount,
            $userWalletAddress
        );
        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Address Assertion"
        );
        $this->assertEquals(
            "te6cckEBAgEAygABsA+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCA7msoEBANklk4VhgAIqFqMWTE1aoxM/MRD/EEluAMqKyKvv/FAn4CTTNIDD6BfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5owAEJ8S6pV9gesOI0M88z1gPHslmVWQc+mNA//J6AESzmi8vLJFA==",
            $result->payload,
            "Payload Assertion"
        );
        $this->assertEquals(BigInteger::of('170000000'), $result->value, "Amount Assertion");

        // Test 3 - should build expected tx params when queryId is defined
        $result = $this->swap->JettonToTonTxParams(
            $proxyTon,
            $userWalletAddress,
            $offerJettonAddress,
            $offerAmount,
            $askAmount,
            queryId: $queryId
        );
        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Address Assertion"
        );
        $this->assertEquals(
            "te6cckEBAgEAqQABsA+KfqUAAAAAAAAwOUHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCA7msoEBAJclk4VhgAIqFqMWTE1aoxM/MRD/EEluAMqKyKvv/FAn4CTTNIDD6BfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQ8gSfBg==",
            $result->payload,
            "Payload Assertion"
        );
        $this->assertEquals(BigInteger::of('170000000'), $result->value, "Amount Assertion");

        // Test 4 - should build expected tx params when custom gasAmount is defined
        $result = $this->swap->JettonToTonTxParams(
            $proxyTon,
            $userWalletAddress,
            $offerJettonAddress,
            $offerAmount,
            $askAmount,
            gasAmount: BigInteger::of(1)
        );
        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Address Assertion"
        );
        $this->assertEquals(
            "te6cckEBAgEAqQABsA+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCA7msoEBAJclk4VhgAIqFqMWTE1aoxM/MRD/EEluAMqKyKvv/FAn4CTTNIDD6BfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQv9h8dw==",
            $result->payload,
            "Payload Assertion"
        );
        $this->assertEquals(BigInteger::of('1'), $result->value, "Amount Assertion");


        // Test 5 - should build expected tx params when custom forwardGasAmount is defined
        $result = $this->swap->JettonToTonTxParams(
            $proxyTon,
            $userWalletAddress,
            $offerJettonAddress,
            $offerAmount,
            $askAmount,
            forwardGasAmount: BigInteger::of(1)
        );
        $this->assertEquals(
            "EQBB_eiDQ9YJ_7UiNsrVvhTKt2O0oKjKe76eVQ7QPS-oYPsi",
            $result->address->toString(true, true, true),
            "Address Assertion"
        );
        $this->assertEquals(
            "te6cckEBAgEApgABqg+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCdAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaAgMBAJclk4VhgAIqFqMWTE1aoxM/MRD/EEluAMqKyKvv/FAn4CTTNIDD6BfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQyZHaEA==",
            $result->payload,
            "Payload Assertion"
        );
        $this->assertEquals(BigInteger::of('170000000'), $result->value, "Amount Assertion");
    }

    public function testTonToJettonTxParams()
    {

        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("getWalletAddress")
            ->willReturnCallback(function ($arg0, $arg1) {
                $askJettonAddress = new Address(ASK_JETTON_ADDRESS);

                if (new Address($arg1) == $askJettonAddress)
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOAAQDOYNkjxevc0Ludmj5oWQzsz/S9vn9/b/V0DLdsOUWw40LsPA==", true)->beginParse()->loadAddress();
                if (new Address($arg1) == (new PtonV1($this->init))->address)
                    return Cell::oneFromBoc("te6ccsEBAQEAJAAAAEOAAioWoxZMTVqjEz8xEP8QSW4AyorIq+/8UCfgJNM0gMPwJB4oTQ==", true)->beginParse()->loadAddress();
            });

        $userWalletAddress = new Address(USER_WALLET_ADDRESS);
        $askJettonAddress = new Address(ASK_JETTON_ADDRESS);
        $proxyTon = new PtonV1($this->init, provider: $mock);
        $offerAmount = BigInteger::of("500000000");
        $askAmount = BigInteger::of("200000000");
        $referralAddress = new Address(USER_WALLET_ADDRESS);
        $queryId = 12345;



        $this->swap = new Swap($this->init, callContractMethod: $mock);

        // TEST 1 - should build expected tx params
        $result = $this->swap->TonToJettonTxParams(
            $proxyTon,
            $userWalletAddress,
            $askJettonAddress,
            $offerAmount,
            $askAmount
        );
        $this->assertEquals(
            "EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC",
            $result->address->toString(true,true,true),
        );
        $this->assertEquals(
            "te6cckEBAgEAiAABbQ+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcECwbgQMBAJclk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQ4VeW3A==",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(685000000),
            $result->value
        );


        // TEST 2 - should build expected tx params when referralAddress is defined
        $result = $this->swap->TonToJettonTxParams(
            $proxyTon,
            $userWalletAddress,
            $askJettonAddress,
            $offerAmount,
            $askAmount,
            referralAddress: $userWalletAddress
        );
        $this->assertEquals(
            "EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC",
            $result->address->toString(true,true,true),
        );
        $this->assertEquals(
            "te6cckEBAgEAqQABbQ+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcECwbgQMBANklk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5owAEJ8S6pV9gesOI0M88z1gPHslmVWQc+mNA//J6AESzmi/Fv4ew==",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(685000000),
            $result->value
        );


        // TEST 3 - should build expected tx params when queryId is defined
        $result = $this->swap->TonToJettonTxParams(
            $proxyTon,
            $userWalletAddress,
            $askJettonAddress,
            $offerAmount,
            $askAmount,
            queryId: $queryId
        );
        $this->assertEquals(
            "EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC",
            $result->address->toString(true,true,true),
        );
        $this->assertEquals(
            "te6cckEBAgEAiAABbQ+KfqUAAAAAAAAwOUHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcECwbgQMBAJclk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQq/XlXw==",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(685000000),
            $result->value
        );



        // TEST 4 - should build expected tx params when custom gasAmount is defined
        $result = $this->swap->TonToJettonTxParams(
            $proxyTon,
            $userWalletAddress,
            $askJettonAddress,
            $offerAmount,
            $askAmount
        );
        $this->assertEquals(
            "EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC",
            $result->address->toString(true,true,true),
        );
        $this->assertEquals(
            "te6cckEBAgEAiAABbQ+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcECwbgQMBAJclk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQ4VeW3A==",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(685000000),
            $result->value
        );


        // TEST 5 - should build expected tx params when custom forwardGasAmount is defined
        $result = $this->swap->TonToJettonTxParams(
            $proxyTon,
            $userWalletAddress,
            $askJettonAddress,
            $offerAmount,
            $askAmount,
            forwardGasAmount: BigInteger::of(1)
        );
        $this->assertEquals(
            "EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC",
            $result->address->toString(true,true,true),
        );
        $this->assertEquals(
            "te6cckEBAgEAhQABZw+KfqUAAAAAAAAAAEHc1lAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcBAcBAJclk4VhgAEAzmDZI8Xr3NC7nZo+aFkM7M/0vb5/f2/1dAy3bDlFqBfXhAEABCfEuqVfYHrDiNDPPM9YDx7JZlVkHPpjQP/yegBEs5oQoDsKGA==",
            $result->payload
        );
        $this->assertEquals(
            BigInteger::of(500000001),
            $result->value
        );
    }
}
