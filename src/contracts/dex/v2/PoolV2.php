<?php

namespace StonFi\contracts\dex\v2;

use Brick\Math\BigInteger;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Boc\Exceptions\SliceException;
use Olifanton\Interop\Boc\SnakeString;
use Olifanton\Interop\Bytes;
use StonFi\const\OpCodes;
use StonFi\const\v2\models\PoolData;
use StonFi\const\v2\gas\pool\BurnGas;
use StonFi\const\v2\gas\pool\CollectFeeGas;
use StonFi\const\v2\models\TransactionParams;
use StonFi\contracts\common\CallContractMethods;
use StonFi\contracts\dex\v1\LpAccount;
use StonFi\Init;

class PoolV2
{
    public Address $routerAddress;
    public Address $poolAddress;
    public CallContractMethods $provider;

    public function __construct(public readonly Init $init, $poolAddress = null, CallContractMethods $provider = null)
    {
        $this->poolAddress = $poolAddress;
        $this->routerAddress = $this->init->getRouter();

        if ($provider != null)
            $this->provider = $provider;
        else
            $this->provider = new CallContractMethods($this->init);
    }

    /**
     * @param mixed $poolAddress
     */
    public function setPoolAddress($poolAddress): void
    {
        $this->poolAddress = $poolAddress;
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
        $result = json_decode($this->provider->runMethod($contractAddress, 'get_pool_address', [
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

    public function getPoolAddressByJettonMinter(string $token0, string $token1)
    {
        $token0WalletAddress = $this->provider->getWalletAddress($this->routerAddress->toString(), $token0);
        $token1WalletAddress = $this->provider->getWalletAddress($this->routerAddress->toString(), $token1);

        return $this->getPoolAddress($token0WalletAddress, $token1WalletAddress);
    }


    public function createCollectFeeBody(
        $queryId = null
    ): Cell
    {
        return (new Builder())
            ->writeUint(OpCodes::COLLECT_FEES, 32)
            ->writeUint($queryId ?? 0, 64)
            ->cell();
    }

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
        Cell       $customPayload = null,
                   $queryId = null
    ): Cell
    {
        return (new Builder())
            ->writeUint(OpCodes::BURN, 32)
            ->writeUint($queryId ?? 0, 64)
            ->writeCoins($amount)
            ->writeAddress(null)
            ->writeMaybeRef($customPayload)
            ->cell();
    }

    /**
     * @throws CellException
     * @throws BitStringException
     * @throws SliceException
     */
    public function getBurnTxParams(

        BigInteger $amount,
        Address    $userWalletAddress,
        Cell       $customPayload = null,
        BigInteger $gasAmount = null,
                   $queryId = null
    ): TransactionParams
    {
        $to = $this->provider->getWalletAddress($this->poolAddress->toString(), $userWalletAddress->toString());

        $body = $this->createBurnBody($amount, $customPayload, $queryId);
        $value = $gasAmount ?? (new BurnGas())->gasAmount;
        return new TransactionParams($to, Bytes::bytesToBase64($body->toBoc(false)), $value);
    }

    /**
     * @throws CellException
     * @throws SliceException
     * @throws BitStringException
     * @throws \Exception
     */
    public function getLpAccountAddress(string $ownerAddress): Address|null
    {
        $ownerAddress = Bytes::bytesToHexString((new Builder())->writeAddress(new Address($ownerAddress))->cell()->toBoc(false));
        $result = json_decode($this->provider->runMethod($this->poolAddress, 'get_lp_account_address', [
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
    public function getJettonWallet(Address $ownerAddress): Address|null
    {
        return $this->provider->getWalletAddress($ownerAddress->toString(true,true,true), $this->poolAddress->toString(true,true,true));
    }

    public function getLpAccount($ownerAddress)
    {
        return new LpAccount($this->init, $this->getLpAccountAddress($ownerAddress));
    }

    public function getPoolData()
    {
        return $this->implGetPoolData();
    }

    public function getPoolType()
    {
        $pool_data = json_decode($this->provider->runMethod($this->poolAddress, "get_pool_type"), true);

        if ($pool_data['success']) {
            $stacks = $pool_data['stack'];
            return SnakeString::parse(Cell::oneFromBoc($stacks[0]['cell']));
        } else {
            throw new \Exception("Contract getter failed. \n " . json_encode($pool_data));
        }
    }

    /**
     * @throws CellException
     * @throws SliceException
     * @throws \Exception
     */
    protected function implGetPoolData(): PoolData
    {
        if ($this->poolAddress == null)
            throw  new \Exception("Pool address is required.");

        $pool_data = json_decode($this->provider->runMethod($this->poolAddress, "get_pool_data"), true);

        if ($pool_data['success']) {
            $stacks = $pool_data['stack'];
            if (count($stacks) != 12)
                throw new \Exception("Stack under/overflow");

            $isLocked = hexdec($stacks[0][$stacks[0]['type']]) == 1;
            $routerWalletAddress = $this->readWalletAddress($stacks[1]);
            $totalSupplyLp = BigInteger::of(hexdec($stacks[2][$stacks[2]['type']]));
            $reserve0 = BigInteger::of(hexdec($stacks[3][$stacks[3]['type']]));
            $reserve1 = BigInteger::of(hexdec($stacks[4][$stacks[4]['type']]));
            $token0WalletAddress = $this->readWalletAddress($stacks[5]);
            $token1WalletAddress = $this->readWalletAddress($stacks[6]);
            $lpFee = BigInteger::of(hexdec($stacks[7][$stacks[7]['type']]));
            $protocolFee = BigInteger::of(hexdec($stacks[8][$stacks[8]['type']]));
            $protocolFeeAddress = $this->readWalletAddress($stacks[9]);
            $collectedToken0ProtocolFee = BigInteger::of(hexdec($stacks[10][$stacks[10]['type']]));
            $collectedToken1ProtocolFee = BigInteger::of(hexdec($stacks[11][$stacks[11]['type']]));


            return new PoolData(
                $isLocked,
                $routerWalletAddress,
                $totalSupplyLp,
                $reserve0,
                $reserve1,
                $token0WalletAddress,
                $token1WalletAddress,
                $lpFee,
                $protocolFee,
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
     */
    public function readWalletAddress($item): Address|null
    {
        return (Cell::oneFromBoc($item[$item['type']])->beginParse()->loadAddress());
    }
}