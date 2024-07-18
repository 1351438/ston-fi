<?php

namespace StonFi\dex\common;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;

class CreateJettonTransferMessage {
    /**
     * @throws BitStringException
     */
    static public function create(
        $queryId = null,
        $amount,
        Address $destination,
        $forwardTonAmount,
        Cell $forwardPayload = null,
        Cell $customPayload = null,
        Address $responseDestination = null
    ): Cell
    {
        $c = new Builder();
        $c
            ->writeUint(0xf8a7ea5, 32)
            ->writeUint($queryId ?? BigInteger::of(0), 64)
            ->writeCoins($amount)
            ->writeAddress($destination)
            ->writeAddress($responseDestination ?? null);
        if ($customPayload != null) {
            $c->writeBit(true);
            $c->writeRef($customPayload);
        } else {
            $c->writeBit(false);
        }
        $c->writeCoins($forwardTonAmount ?? 0);
        if ($forwardPayload != null) {
            $c->writeBit(true);
            $c->writeRef($forwardPayload);
        } else {
            $c->writeBit(false);
        }
        return $c->cell();
    }
}