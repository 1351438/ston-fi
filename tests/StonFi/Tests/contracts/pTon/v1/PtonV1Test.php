<?php

namespace StonFi\Tests\contracts\pTon\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use Olifanton\Interop\Units;
use StonFi\contracts\common\CallContractMethods;
use StonFi\enums\Networks;
use StonFi\Init;
use StonFi\pTON\v1\PtonV1;
use PHPUnit\Framework\TestCase;
use const StonFi\Tests\contracts\dex\ASK_JETTON_ADDRESS;
use const StonFi\Tests\contracts\dex\OFFER_JETTON_ADDRESS;

const USER_WALLET_ADDRESS = "EQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaB3i";
class PtonV1Test extends TestCase
{
    function testAddress()
    {
        $init = new Init(Networks::MAINNET);
        $pton = new PtonV1($init);
        $this->assertTrue((new Address("EQCM3B12QK1e4yZSf8GtBRT0aLMNyEsBc_DhVfRRtOEffLez"))->isEqual($pton->address));

        $pton = new PtonV1($init, new Address(USER_WALLET_ADDRESS));
        $this->assertTrue((new Address(USER_WALLET_ADDRESS))->isEqual($pton->address));
    }

    private function generateProviderMock()
    {
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("getWalletAddress")
            ->willReturnCallback(function ($userAddr, $jettonAddr) {
                    return Cell::oneFromBoc("te6cckEBAQEAJAAAQ4ANNPwBsCJlaV4Is5qsUUPuPdEGsgv4gpjyE/tn9VHWnzAClbSC", true)->beginParse()->loadAddress();
            });
        return $mock;
    }
    public function testGetTonTransferTxParams()
    {
        $init = new Init(Networks::MAINNET);
        $pton = new PtonV1($init, provider: $this->generateProviderMock());

        $result = $pton->getTonTransferTxParams(
            tonAmount: Units::toNano('1'),
            destinationAddress: new Address("EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt"),
            refundAddress: new Address(USER_WALLET_ADDRESS)
        );

        $this->assertTrue($result->address->isEqual(new Address("EQBpp-ANgRMrSvBFnNViih9x7og1kF_EFMeQn9s_qo60-eML")));
        $this->assertEquals("te6cckEBAQEANQAAZQ+KfqUAAAAAAAAAAEO5rKAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcAdFEo1U=", $result->payload);
        $this->assertEquals("1000000000", $result->value);



        $result = $pton->getTonTransferTxParams(
            tonAmount: Units::toNano('1'),
            destinationAddress: new Address("EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt"),
            refundAddress: new Address(USER_WALLET_ADDRESS),
            queryId: 12345
        );

        $this->assertTrue($result->address->isEqual(new Address("EQBpp-ANgRMrSvBFnNViih9x7og1kF_EFMeQn9s_qo60-eML")));
        $this->assertEquals("te6cckEBAQEANQAAZQ+KfqUAAAAAAAAwOUO5rKAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcAT6CEJk=", $result->payload);
        $this->assertEquals("1000000000", $result->value);



        $result = $pton->getTonTransferTxParams(
            tonAmount: Units::toNano('1'),
            destinationAddress: new Address("EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt"),
            refundAddress: new Address(USER_WALLET_ADDRESS),
            forwardPayload: (new Builder())->cell(),
            forwardTonAmount: Units::toNano('0.1')
        );

        $this->assertTrue($result->address->isEqual(new Address("EQBpp-ANgRMrSvBFnNViih9x7og1kF_EFMeQn9s_qo60-eML")));
        $this->assertEquals("te6cckEBAgEAPAABbQ+KfqUAAAAAAAAAAEO5rKAIAO87mQKicbKgHIk4pSPP4k5xhHqutqYgAB7USnesDnCcEBfXhAMBAACDD/1r", $result->payload);
        $this->assertEquals("1100000000", $result->value);
    }


    public function testCreateDeployWalletBody()
    {
        $init = new Init(Networks::MAINNET);
        $pton = new PtonV1($init, provider: $this->generateProviderMock());

        $body = $pton->createDeployWalletBody(ownerAddress: new Address(USER_WALLET_ADDRESS));
        $this->assertEquals('te6cckEBAQEAMAAAW2zENXMAAAAAAAAAAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRD9A4zf', Bytes::bytesToBase64($body->toBoc(false)));


        $body = $pton->createDeployWalletBody(ownerAddress: new Address(USER_WALLET_ADDRESS), queryId: 12345);
        $this->assertEquals('te6cckEBAQEAMAAAW2zENXMAAAAAAAAwOYACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRA0o0Ga', Bytes::bytesToBase64($body->toBoc(false)));
    }

    public function testGetDeployWalletTxParams()
    {
        $init = new Init(Networks::MAINNET);
        $pton = new PtonV1($init, provider: $this->generateProviderMock());

        $result = $pton->getDeployWalletTxParams(ownerAddress: new Address(USER_WALLET_ADDRESS));

        $this->assertTrue($result->address->isEqual(new Address("EQCM3B12QK1e4yZSf8GtBRT0aLMNyEsBc_DhVfRRtOEffLez")));
        $this->assertEquals("te6cckEBAQEAMAAAW2zENXMAAAAAAAAAAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRD9A4zf", $result->payload);
        $this->assertEquals("1050000000", $result->value);



        $result = $pton->getDeployWalletTxParams(ownerAddress: new Address(USER_WALLET_ADDRESS), queryId: 12345);

        $this->assertTrue($result->address->isEqual(new Address("EQCM3B12QK1e4yZSf8GtBRT0aLMNyEsBc_DhVfRRtOEffLez")));
        $this->assertEquals("te6cckEBAQEAMAAAW2zENXMAAAAAAAAwOYACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRA0o0Ga", $result->payload);
        $this->assertEquals("1050000000", $result->value);



        $result = $pton->getDeployWalletTxParams(ownerAddress: new Address(USER_WALLET_ADDRESS), gasAmount: BigInteger::of(1));

        $this->assertTrue($result->address->isEqual(new Address("EQCM3B12QK1e4yZSf8GtBRT0aLMNyEsBc_DhVfRRtOEffLez")));
        $this->assertEquals("te6cckEBAQEAMAAAW2zENXMAAAAAAAAAAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRD9A4zf", $result->payload);
        $this->assertEquals("1", $result->value);
    }
}
