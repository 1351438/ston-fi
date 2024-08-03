<?php

namespace StonFi\contracts\dex\v1;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Boc\Exceptions\SliceException;
use Olifanton\Interop\Bytes;
use StonFi\const\v1\gas\pool\BurnGas;
use StonFi\const\v1\gas\pool\CollectFeeGas;
use StonFi\const\v1\models\PoolData;
use StonFi\const\v1\models\PoolExpectedAmount;
use StonFi\const\v1\models\PoolExpectedOutput;
use StonFi\const\v1\models\TransactionParams;
use StonFi\const\OpCodes;
use StonFi\contracts\api\v1\Jetton;
use StonFi\contracts\common\CallContractMethods;
use StonFi\Init;

class Pool
{
    public $routerAddress;
    public $poolAddress;
    public $CallContractMethods;

    public function __construct(public readonly Init $init, $poolAddress = null, $CallContractMethods = null)
    {
        $this->poolAddress = $poolAddress;
        $this->routerAddress = $this->init->getRouter();

        if (isset($CallContractMethods))
            $this->CallContractMethods = $CallContractMethods;
        else
            $this->CallContractMethods = new CallContractMethods($this->init);
    }

    /**
     * @param mixed $poolAddress
     */
    public function setPoolAddress($poolAddress): void
    {
        $this->poolAddress = $poolAddress;
    }

    /**
     * @throws BitStringException
     */
    public function createCollectFeeBody(
        $queryId = null
    ): Cell
    {
        return (new Builder())
            ->writeUint(OpCodes::COLLECT_FEES, 32)
            ->writeUint($queryId ?? 0, 64)
            ->cell();
    }


    /**
     * Build all data required to execute a `collect_fees` transaction.
     *
     * @param BigInteger|null $gasAmount
     * @param $queryId
     * @return TransactionParams
     * @throws BitStringException
     * @throws CellException
     */

    public function getCollectFeeTxParams(
        BigInteger $gasAmount = null,
                   $queryId = null
    ): TransactionParams
    {
        $to = $this->poolAddress;

        $body = $this->createCollectFeeBody($queryId);

        $value = $gasAmount ?? (new CollectFeeGas())->gasAmount;

        return new TransactionParams(new Address($to), Bytes::bytesToBase64($body->toBoc(false)), $value);
    }


    /**
     * @throws BitStringException
     */
    public function createBurnBody(
        BigInteger $amount,
        Address    $responseAddress,
                   $queryId = null
    ): Cell
    {
        return (new Builder())
            ->writeUint(OpCodes::BURN, 32)
            ->writeUint($queryId ?? 0, 64)
            ->writeCoins($amount)
            ->writeAddress($responseAddress)
            ->cell();
    }

    /**
     * @throws CellException
     * @throws BitStringException
     */
    public function getBurnTxParams(

        BigInteger $amount,
        Address    $responseAddress,
        BigInteger $gasAmount = null,
                   $queryId = null
    ): TransactionParams
    {
        $to = $this->CallContractMethods->getWalletAddress($this->poolAddress, $responseAddress->toString());

        $body = $this->createBurnBody($amount, $responseAddress, $queryId);
        $value = $gasAmount ?? (new BurnGas())->gasAmount;
        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    /**
     * Estimate expected result of the amount of jettonWallet tokens swapped to the other type of tokens of the pool
     * @throws \Exception
     */
    public function getExpectedOutputs(
        BigInteger $amount,
        string     $jettonWallet
    ): PoolExpectedOutput
    {
        ;
        $jettonWallet = Bytes::bytesToHexString((new Builder())->writeAddress(new Address($jettonWallet))->cell()->toBoc(false));
        $result = json_decode($this->CallContractMethods->call($this->poolAddress, "get_expected_outputs", [
            $jettonWallet,
            $amount->__toString(),
        ]), true);

        if ($result['success']) {
            $stack = $result['stack'];
            if (count($stack) != 3)
                throw new \Exception("Stack under/overflow");
            $jettonToReceive = BigInteger::of(hexdec($stack[0][$stack[0]['type']]));
            $protocolFeePaid = BigInteger::of(hexdec($stack[1][$stack[1]['type']]));
            $refFeePaid = BigInteger::of(hexdec($stack[2][$stack[2]['type']]));

            return new PoolExpectedOutput($jettonToReceive, $protocolFeePaid, $refFeePaid);
        } else {
            throw new \Exception("Contract getter failed. \n " . json_encode($result));
        }
    }

    /**
     * @throws \Exception
     */
    public function getExpectedTokens(BigInteger $amount0, BigInteger $amount1)
    {
        $result = json_decode($this->CallContractMethods->call($this->poolAddress, "get_expected_tokens", [
            $amount1,
            $amount0,
        ]), true);
        if ($result['success']) {
            $stack = $result['stack'];
            if (count($stack) != 1)
                throw new \Exception("Stack under/overflow");

            return BigInteger::of(hexdec($stack[0][$stack[0]['type']]));
        } else {
            throw new \Exception("Contract getter failed. \n " . json_encode($result));
        }
    }

    /**
     * @throws \Exception
     */
    public function getExpectedLiquidity(BigInteger $jettonAmount): PoolExpectedAmount
    {
        $result = json_decode($this->CallContractMethods->call($this->poolAddress, "get_expected_liquidity", [
            $jettonAmount
        ]), true);
        if ($result['success']) {
            $stack = $result['stack'];
            if (count($stack) != 2)
                throw new \Exception("Stack under/overflow");

            return new PoolExpectedAmount(BigInteger::of(hexdec($stack[0][$stack[0]['type']])), BigInteger::of(hexdec($stack[1][$stack[1]['type']])));
        } else {
            throw new \Exception("Contract getter failed. \n " . json_encode($result));
        }
    }

    /**
     * @throws CellException
     * @throws SliceException
     * @throws \Exception
     */
    public function getPoolData(): PoolData
    {
        if ($this->poolAddress == null)
            throw  new \Exception("Pool address is required.");

        $pool_data = json_decode($this->CallContractMethods->call($this->poolAddress, "get_pool_data"), true);

        if ($pool_data['success']) {
            $stacks = $pool_data['stack'];
            if (count($stacks) != 10)
                throw new \Exception("Stack under/overflow");
            $reserve0 = BigInteger::of(hexdec($stacks[0][$stacks[0]['type']]));
            $reserve1 = BigInteger::of(hexdec($stacks[1][$stacks[1]['type']]));
            $token0WalletAddress = $this->readWalletAddress($stacks[2]);
            $token1WalletAddress = $this->readWalletAddress($stacks[3]);
            $lpFee = BigInteger::of(hexdec($stacks[4][$stacks[4]['type']]));
            $protocolFee = BigInteger::of(hexdec($stacks[5][$stacks[5]['type']]));
            $refFee = BigInteger::of(hexdec($stacks[6][$stacks[6]['type']]));
            $protocolFeeAddress = $this->readWalletAddress($stacks[7]);
            $collectedToken0ProtocolFee = BigInteger::of(hexdec($stacks[8][$stacks[8]['type']]));
            $collectedToken1ProtocolFee = BigInteger::of(hexdec($stacks[9][$stacks[9]['type']]));

            return new PoolData(
                $reserve0,
                $reserve1,
                $token0WalletAddress,
                $token1WalletAddress,
                $lpFee,
                $protocolFee,
                $refFee,
                $protocolFeeAddress,
                $collectedToken0ProtocolFee,
                $collectedToken1ProtocolFee,
            );
        } else {
            throw new \Exception("Contract getter failed. \n " . json_encode($pool_data));
        }
    }

    /**
     * @throws CellException
     * @throws SliceException
     * @throws BitStringException
     * @throws \Exception
     */
    public function getPoolAddress(string $token0, string $token1): Address
    {
        $contractAddress = (new Address($this->routerAddress))->toString(false, true);
        $token0 = Bytes::bytesToHexString((new Builder())->writeAddress(new Address($token0))->cell()->toBoc(false));
        $token1 = Bytes::bytesToHexString((new Builder())->writeAddress(new Address($token1))->cell()->toBoc(false));
        $result = json_decode($this->CallContractMethods->call($contractAddress, 'get_pool_address', [
            $token1,
            $token0,
        ]), true);
        if ($result['success']) {
            $stack = $result['stack'];
            $item = $stack[0];
            return $this->readWalletAddress($item);
        } else {
            throw new \Exception("Contract getter failed. \n " . json_encode($result));
        }
    }


    /**
     * @throws CellException
     * @throws SliceException
     */
    public function readWalletAddress($item): Address
    {
        return new Address(Cell::oneFromBoc($item[$item['type']])->beginParse()->loadAddress());
    }


    /**
     * @throws CellException
     * @throws SliceException
     * @throws BitStringException
     */
    public function getPoolAddressByJettonMinter(string $token0, string $token1)
    {
        $token0WalletAddress = $this->CallContractMethods->getWalletAddress($this->routerAddress->toString(), $token0);
        $token1WalletAddress = $this->CallContractMethods->getWalletAddress($this->routerAddress->toString(), $token1);

        return $this->getPoolAddress($token0WalletAddress, $token1WalletAddress);
    }


    /**
     * @throws CellException
     * @throws SliceException
     * @throws BitStringException
     * @throws \Exception
     */
    public function getLpAccountAddress(string $ownerAddress): Address
    {
        $contractAddress = (new Address($this->poolAddress))->toString(false, true);
        $ownerAddress = Bytes::bytesToHexString((new Builder())->writeAddress(new Address($ownerAddress))->cell()->toBoc(false));
        $result = json_decode($this->CallContractMethods->call($contractAddress, 'get_lp_account_address', [
            $ownerAddress,
        ]), true);

        if ($result['success']) {
            $stack = $result['stack'];
            $item = $stack[0];
            return $this->readWalletAddress($item);
        } else {
            throw new \Exception("Contract getter failed. \n " . json_encode($result));
        }
    }

    /**
     * @throws CellException
     * @throws SliceException
     * @throws BitStringException
     */
    public function getLpAccount($ownerAddress): LpAccount
    {
        $addr = $this->getLpAccountAddress($ownerAddress);
        if ($addr)
            return new LpAccount($this->init, $addr, $this->CallContractMethods);

        throw new \Exception("Getting address failed.");
    }
}