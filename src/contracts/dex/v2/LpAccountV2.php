<?php

namespace StonFi\contracts\dex\v2;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Bytes;
use StonFi\const\OpCodes;
use StonFi\const\v2\gas\lp_account\DirectAddLpGas;
use StonFi\const\v2\gas\lp_account\RefundGas;
use StonFi\const\v2\gas\lp_account\ResetGas;
use StonFi\const\v2\models\LpAccountData;
use StonFi\const\v2\models\TransactionParams;
use StonFi\contracts\common\CallContractMethods;
use StonFi\Init;

class LpAccountV2
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

    public function createRefundBody($leftMaybePayload = null, $rightMaybePayload = null, $queryId = null): Cell
    {
        return (new Builder())
            ->writeUint(OpCodes::REFUND_ME, 32)
            ->writeUint($queryId ?? 0, 64)
            ->writeMaybeRef($leftMaybePayload)
            ->writeMaybeRef($rightMaybePayload)
            ->cell();
    }

    public function getRefundTxParams($leftMaybePayload = null, $rightMaybePayload = null, BigInteger $gasAmount = null, $queryId = null)
    {
        $to = $this->lpAccountAddress;
        $body = $this->createRefundBody(
            $leftMaybePayload,
            $rightMaybePayload,
            $queryId
        );
        $value = $gasAmount ?? (new RefundGas())->gasAmount;

        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    /**
     * @throws BitStringException
     */
    public function createDirectAddLiquidityBody(
        BigInteger $amount0,
        BigInteger $amount1,
        Address    $userWalletAddress,
        BigInteger $minimumLpToMint = null,
        Address    $refundAddress = null,
        Address    $excessAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
                   $queryId = null
    )
    {
        return (new Builder())
            ->writeUint(OpCodes::DIRECT_ADD_LIQUIDITY, 32)
            ->writeUint($queryId ?? 0, 64)
            ->writeCoins($amount0)
            ->writeCoins($amount1)
            ->writeCoins($minimumLpToMint ?? 1)
            ->writeCoins($customPayloadForwardGasAmount ?? 0)
            ->writeAddress($userWalletAddress)
            ->writeMaybeRef($customPayload)
            ->writeRef(
                (new Builder())
                    ->writeAddress($refundAddress ?? $userWalletAddress)
                    ->writeAddress($excessAddress ?? $refundAddress ?? $userWalletAddress)
                    ->cell()
            )
            ->cell();
    }

    public function getDirectAddLiquidityTxParams(
        Address    $userWalletAddress,
        BigInteger $amount0,
        BigInteger $amount1,
        BigInteger $minimumLpToMint = null,
        Address    $refundAddress = null,
        Address    $excessAddress = null,
        Cell       $customPayload = null,
        BigInteger $customPayloadForwardGasAmount = null,
        BigInteger $gasAmount = null,
                   $queryId = null
    )
    {
        $to = $this->lpAccountAddress;
        $body = $this->createDirectAddLiquidityBody(
            amount0: $amount0,
            amount1: $amount1,
            userWalletAddress: $userWalletAddress,
            minimumLpToMint: $minimumLpToMint,
            refundAddress: $refundAddress,
            excessAddress: $excessAddress,
            customPayload: $customPayload,
            customPayloadForwardGasAmount: $customPayloadForwardGasAmount,
            queryId: $queryId
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

        $result = json_decode($this->callContractMethods->call($this->lpAccountAddress, "get_lp_account_data"), true);

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
        return new Address(Cell::oneFromBoc($item[$item['type']])->beginParse()->loadAddress());
    }
}