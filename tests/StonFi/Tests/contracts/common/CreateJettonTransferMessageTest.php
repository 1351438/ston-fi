<?php

namespace StonFi\Tests\contracts\common;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Bytes;
use StonFi\contracts\common\CreateJettonTransferMessage;
use PHPUnit\Framework\TestCase;
use function Olifanton\Ton\Marshalling\Tvm\cell;

class CreateJettonTransferMessageTest extends TestCase
{
    public function testCreate()
    {
        $c = CreateJettonTransferMessage::create(
            1,
            amount: BigInteger::of('1000000000'),
            destination: new Address("EQB3YmWW5ZLhe2gPUAw550e2doyWnkj5hzv3TXp2ekpAWe7v"),
            forwardTonAmount: BigInteger::of("500000000")
        );

        $this->assertEquals(
            "te6cckEBAQEAOQAAbQ+KfqUAAAAAAAAAAUO5rKAIAO7Eyy3LJcL20B6gGHPOj2ztGS08kfMOd+6a9Oz0lICyEHc1lAGwz8AH",
            Bytes::bytesToBase64($c->toBoc(false))
        );

        $c2 = CreateJettonTransferMessage::create(
            1,
            amount: BigInteger::of('1000000000'),
            destination: new Address("EQB3YmWW5ZLhe2gPUAw550e2doyWnkj5hzv3TXp2ekpAWe7v"),
            forwardTonAmount: BigInteger::of("500000000"),
            customPayload: (new Builder())->writeUint(1, 32)->cell(),
        );
        $this->assertEquals(
            "te6cckEBAgEAQAABbQ+KfqUAAAAAAAAAAUO5rKAIAO7Eyy3LJcL20B6gGHPOj2ztGS08kfMOd+6a9Oz0lICyUHc1lAEBAAgAAAABFDJIHA==",
            Bytes::bytesToBase64($c2->toBoc(false))
        );


        $c3 = CreateJettonTransferMessage::create(
            1,
            amount: BigInteger::of('1000000000'),
            destination: new Address("EQB3YmWW5ZLhe2gPUAw550e2doyWnkj5hzv3TXp2ekpAWe7v"),
            forwardTonAmount: BigInteger::of("500000000"),
            forwardPayload: (new Builder())->writeUint(2, 32)->cell(),
        );
        $this->assertEquals(
            "te6cckEBAgEAQAABbQ+KfqUAAAAAAAAAAUO5rKAIAO7Eyy3LJcL20B6gGHPOj2ztGS08kfMOd+6a9Oz0lICyEHc1lAMBAAgAAAAC4lvH+Q==",
            Bytes::bytesToBase64($c3->toBoc(false))
        );


        $c4 = CreateJettonTransferMessage::create(
            1,
            amount: BigInteger::of('1000000000'),
            destination: new Address("EQB3YmWW5ZLhe2gPUAw550e2doyWnkj5hzv3TXp2ekpAWe7v"),
            forwardTonAmount: BigInteger::of("500000000"),
            responseDestination: new Address('EQAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D_8noARLOaB3i')
        );
        $this->assertEquals(
            "te6cckEBAQEAWgAAsA+KfqUAAAAAAAAAAUO5rKAIAO7Eyy3LJcL20B6gGHPOj2ztGS08kfMOd+6a9Oz0lICzAAQnxLqlX2B6w4jQzzzPWA8eyWZVZBz6Y0D/8noARLOaCDuaygBjL74c",
            Bytes::bytesToBase64($c4->toBoc(false))
        );
    }
}
