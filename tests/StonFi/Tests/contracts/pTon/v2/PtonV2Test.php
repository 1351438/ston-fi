<?php

namespace StonFi\Tests\contracts\pTon\v2;

use Brick\Math\BigInteger;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Bytes;
use Olifanton\Interop\Units;
use StonFi\contracts\common\CallContractMethods;
use StonFi\enums\Networks;
use StonFi\Init;
use StonFi\pTON\v2\PtonV2;
use PHPUnit\Framework\TestCase;

const USER_WALLET_ADDRESS = new Address("EQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaB3i");
const PROXY_TON_ADDRESS = new Address("EQDwpyxrmYQlGDViPk-oqP4XK6J11I-bx7fJAlQCWmJB4tVy");

class PtonV2Test extends TestCase
{
    private function generateProviderMock()
    {
        $mock = $this->createMock(CallContractMethods::class);
        $mock->expects($this->any())
            ->method("getWalletAddress")
            ->willReturnCallback(function ($userAddr, $jettonAddr) {
                return Cell::oneFromBoc("te6cckEBAQEAJAAAQ4AInphPXsxLvV8GYIv91ynjTlgXyM3PUU8BZds8WqBZJbCdAP60", true)->beginParse()->loadAddress();
            });
        return $mock;
    }

    public function testGetTonTransferTxParams()
    {
        $init = new Init(Networks::MAINNET);
        $pton = new PtonV2($init, address: PROXY_TON_ADDRESS, provider: $this->generateProviderMock());

        $result = $pton->getTonTransferTxParams(
            contractAddress: USER_WALLET_ADDRESS,
            tonAmount: Units::toNano('1'),
            destinationAddress: new Address("EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt"),
            refundAddress: USER_WALLET_ADDRESS
        );

        $this->assertTrue($result->address->isEqual(new Address("EQBE9MJ69mJd6vgzBF_uuU8acsC-Rm56ingLLtni1QLJLbEL")));
        $this->assertEquals("te6cckEBAQEANAAAYwHzg10AAAAAAAAAAEO5rKAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRRFUPGw==", $result->payload);
        $this->assertEquals("1010000000", $result->value);


        $result = $pton->getTonTransferTxParams(
            contractAddress: USER_WALLET_ADDRESS,
            tonAmount: Units::toNano('1'),
            destinationAddress: new Address("EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt"),
            refundAddress: USER_WALLET_ADDRESS,
            forwardPayload: (new Builder())->cell(),
            forwardTonAmount: Units::toNano("0.1")
        );

        $this->assertTrue($result->address->isEqual(new Address("EQBE9MJ69mJd6vgzBF_uuU8acsC-Rm56ingLLtni1QLJLbEL")));
        $this->assertEquals("te6cckEBAgEANwABZAHzg10AAAAAAAAAAEO5rKAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRAQAAEQ6zpA==", $result->payload);
        $this->assertEquals("1110000000", $result->value);


        $result = $pton->getTonTransferTxParams(
            contractAddress: USER_WALLET_ADDRESS,
            tonAmount: Units::toNano('1'),
            destinationAddress: new Address("EQB3ncyBUTjZUA5EnFKR5_EnOMI9V1tTEAAPaiU71gc4TiUt"),
            refundAddress: USER_WALLET_ADDRESS,
            queryId: 12345
        );

        $this->assertTrue($result->address->isEqual(new Address("EQBE9MJ69mJd6vgzBF_uuU8acsC-Rm56ingLLtni1QLJLbEL")));
        $this->assertEquals("te6cckEBAQEANAAAYwHzg10AAAAAAAAwOUO5rKAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzR24kJEw==", $result->payload);
        $this->assertEquals("1010000000", $result->value);
    }


    public function testCreateDeployWalletBody()
    {
        $init = new Init(Networks::MAINNET);
        $pton = new PtonV2($init, address: PROXY_TON_ADDRESS, provider: $this->generateProviderMock());

        $body = $pton->createDeployWalletBody(ownerAddress: USER_WALLET_ADDRESS, excessAddress: USER_WALLET_ADDRESS);
        $this->assertEquals('te6cckEBAQEAUQAAnU9fQxMAAAAAAAAAAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaJL9mH4', Bytes::bytesToBase64($body->toBoc(false)));


        $body = $pton->createDeployWalletBody(ownerAddress: USER_WALLET_ADDRESS, excessAddress: USER_WALLET_ADDRESS, queryId: 12345);
        $this->assertEquals('te6cckEBAQEAUQAAnU9fQxMAAAAAAAAwOYACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaL1jU6c', Bytes::bytesToBase64($body->toBoc(false)));
    }

    /**
     * @throws CellException
     * @throws DivisionByZeroException
     * @throws NumberFormatException
     */
    public function testGetDeployWalletTxParams()
    {
        $init = new Init(Networks::MAINNET);
        $pton = new PtonV2($init, PROXY_TON_ADDRESS);

        //should build expected tx params
        $result = $pton->getDeployWalletTxParams(ownerAddress: USER_WALLET_ADDRESS);

        $this->assertTrue($result->address->isEqual(new Address("EQDwpyxrmYQlGDViPk-oqP4XK6J11I-bx7fJAlQCWmJB4tVy")));
        $this->assertEquals("te6cckEBAQEAUQAAnU9fQxMAAAAAAAAAAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaJL9mH4", $result->payload);
        $this->assertEquals("100000000", $result->value);

        //should build expected tx params when queryId is defined
        $result = $pton->getDeployWalletTxParams(ownerAddress: USER_WALLET_ADDRESS, queryId: 12345);

        $this->assertTrue($result->address->isEqual(new Address("EQDwpyxrmYQlGDViPk-oqP4XK6J11I-bx7fJAlQCWmJB4tVy")));
        $this->assertEquals("te6cckEBAQEAUQAAnU9fQxMAAAAAAAAwOYACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaL1jU6c", $result->payload);
        $this->assertEquals("100000000", $result->value);

        //should build expected tx params when custom gasAmount is defined
        $result = $pton->getDeployWalletTxParams(ownerAddress: USER_WALLET_ADDRESS, gasAmount: BigInteger::of(1));

        $this->assertTrue($result->address->isEqual(new Address("EQDwpyxrmYQlGDViPk-oqP4XK6J11I-bx7fJAlQCWmJB4tVy")));
        $this->assertEquals("te6cckEBAQEAUQAAnU9fQxMAAAAAAAAAAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaJL9mH4", $result->payload);
        $this->assertEquals("1", $result->value);

        //should build expected tx params when excessAddress is defined
        $pton = new PtonV2($init, "EQCM3B12QK1e4yZSf8GtBRT0aLMNyEsBc_DhVfRRtOEffLez");
        $result = $pton->getDeployWalletTxParams(ownerAddress: USER_WALLET_ADDRESS, excessAddress: $pton->address);

        $this->assertTrue($result->address->isEqual(new Address("EQCM3B12QK1e4yZSf8GtBRT0aLMNyEsBc_DhVfRRtOEffLez")));
        $this->assertEquals("te6cckEBAQEAUQAAnU9fQxMAAAAAAAAAAIACE+JdUq+wPWHEaGeeZ6wHj2SzKrIOfTGgf/k9ACJZzRACM3B12QK1e4yZSf8GtBRT0aLMNyEsBc/DhVfRRtOEffJbNd9u", $result->payload);
        $this->assertEquals("100000000", $result->value);
    }
}
