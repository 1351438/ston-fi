<?php

namespace StonFi\const\v1\models;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;

class  PoolData
{
    public function __construct(
        public readonly BigInteger $reserve0,
        public readonly BigInteger $reserve1,
        public readonly Address $token0WalletAddress,
        public readonly Address $token1WalletAddress,
        public readonly BigInteger $lpFee,
        public readonly BigInteger $protocolFee,
        public readonly BigInteger $refFee,
        public readonly Address $protocolFeeAddress,
        public readonly BigInteger $collectedToken0ProtocolFee,
        public readonly BigInteger $collectedToken1ProtocolFee,
    )
    {
    }

    public function toMap()
    {
        return json_encode([
            'reserve0' => $this->reserve0,
            'reserve1' => $this->reserve1,
            'token0WalletAddress' => $this->token0WalletAddress->toString(),
            'token1WalletAddress' => $this->token1WalletAddress->toString(),
            'lpFee' => $this->lpFee,
            'protocolFee' => $this->protocolFee,
            'refFee' => $this->refFee,
            'protocolFeeAddress' => $this->protocolFeeAddress->toString(),
            'collectedToken0ProtocolFee'=>$this->collectedToken0ProtocolFee,
            'collectedToken1ProtocolFee'=>$this->collectedToken1ProtocolFee,
        ], 128);
    }
}