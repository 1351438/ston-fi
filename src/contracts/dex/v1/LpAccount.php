<?php

namespace StonFi\contracts\dex\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Bytes;
use StonFi\const\OpCodes;
use StonFi\const\v1\gas\lp_account\DirectAddLpGas;
use StonFi\const\v1\gas\lp_account\RefundGas;
use StonFi\const\v1\gas\lp_account\ResetGas;
use StonFi\const\v1\models\LpAccountData;
use StonFi\const\v1\models\PoolData;
use StonFi\const\v1\models\TransactionParams;
use StonFi\contracts\common\CallContractMethods;
use StonFi\Init;

class LpAccount
{
    public Init $init;
    private CallContractMethods $callContractMethods;

    protected Address $lpAccountAddress;

    public function __construct(Init $init, Address $lpAccountAddress, CallContractMethods $CallContractMethods = null)
    {
        $this->init = $init;
        $this->lpAccountAddress = $lpAccountAddress;

        if ($CallContractMethods == null)
            $this->callContractMethods = new CallContractMethods($init);
        else
            $this->callContractMethods = $CallContractMethods;
    }

    public function createRefundBody($queryId = null): Cell
    {
        return (new Builder())
            ->writeUint(OpCodes::REFUND_ME, 32)
            ->writeUint($queryId ?? 0, 64)->cell();
    }

    public function getRefundTxParams(BigInteger $gasAmount = null, $queryId = null)
    {
        $to = $this->lpAccountAddress;
        $body = $this->createRefundBody(
            $queryId
        );
        $value = $gasAmount ?? (new RefundGas())->gasAmount;

        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    public function createDirectAddLiquidityBody(
        BigInteger $amount0,
        BigInteger $amount1,
        BigInteger $minimumLpToMint = null,
                   $queryId = null
    )
    {
        return (new Builder())
            ->writeUint(OpCodes::DIRECT_ADD_LIQUIDITY, 32)
            ->writeUint($queryId ?? 0, 64)
            ->writeCoins($amount0)
            ->writeCoins($amount1)
            ->writeCoins($minimumLpToMint ?? 1)
            ->cell();
    }

    public function getDirectAddLiquidityTxParams(
        BigInteger $amount0,
        BigInteger $amount1,
        BigInteger $minimumLpToMint = null,
        BigInteger $gasAmount = null,
                   $queryId = null
    )
    {
        $to = $this->lpAccountAddress;
        $body = $this->createDirectAddLiquidityBody(
            $amount0,
            $amount1,
            $minimumLpToMint,
            $queryId
        );
        $value = $gasAmount ?? (new DirectAddLpGas())->gasAmount;

        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    public function createResetGasBody($queryId = null)
    {
        return (new Builder())
            ->writeUint(OpCodes::RESET_GAS, 32)
            ->writeUint($queryId ?? 0, 64)
            ->cell();
    }

    public function getResetGasTxParams(
        BigInteger $gasAmount = null,
                   $queryId = null
    )
    {
        $to = $this->lpAccountAddress;

        $body = $this->createResetGasBody(
            $queryId
        );

        $value = $gasAmount ?? (new ResetGas())->gasAmount;
        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    public function getLpAccountData()
    {
        if ($this->lpAccountAddress == null)
            throw  new \Exception("LP Account address is required.");

        $result = json_decode($this->callContractMethods->runMethod($this->lpAccountAddress, "get_lp_account_data"), true);

        if ($result['success']) {
            $stacks = $result['stack'];
            if (count($stacks) != 4)
                throw new \Exception("Stack under/overflow");
            $userAddress = $this->readWalletAddress($stacks[0]);
            $poolAddress = $this->readWalletAddress($stacks[1]);
            $amount0 = BigInteger::of(hexdec($stacks[2][$stacks[2]['type']]));
            $amount1 = BigInteger::of(hexdec($stacks[3][$stacks[3]['type']]));

            return new LpAccountData(
                $userAddress,
                $poolAddress,
                $amount0,
                $amount1,
            );
        } else {
            throw new \Exception("Contract getter failed. \n");
        }
    }


    public function readWalletAddress($item): Address
    {
        return (Cell::oneFromBoc($item[$item['type']])->beginParse()->loadAddress());
    }
}