<?php

namespace StonFi\contracts\dex\v2;

use Brick\Math\BigInteger;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Olifanton\Interop\Address;
use Olifanton\Interop\Boc\Builder;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Exceptions\BitStringException;
use Olifanton\Interop\Boc\Exceptions\CellException;
use Olifanton\Interop\Boc\Exceptions\SliceException;
use Olifanton\Interop\Bytes;
use StonFi\const\OpCodes;
use StonFi\const\v2\gas\vault\WithdrawFeeGas;
use StonFi\const\v2\models\TransactionParams;
use StonFi\const\v2\models\VaultData;
use StonFi\contracts\common\CallContractMethods;
use StonFi\Init;

class VaultV2
{
    public Address $routerAddress;
    public Address $vaultAddress;
    public CallContractMethods $provider;

    public function __construct(public readonly Init $init, $vaultAddress = null, $provider = null)
    {
        $this->vaultAddress = $vaultAddress;
        $this->routerAddress = $this->init->getRouter();

        if (isset($CallContractMethods))
            $this->provider = $provider;
        else
            $this->provider = new CallContractMethods($this->init);
    }

    /**
     * @param Address $vaultAddress
     */
    public function setVaultAddress(Address $vaultAddress): void
    {
        $this->vaultAddress = $vaultAddress;
    }


    /**
     * @throws CellException
     * @throws SliceException
     * @throws BitStringException
     * @throws \Exception
     */
    public function getVaultAddress(string $user, string $tokenWallet): Address
    {
        $contractAddress = (new Address($this->routerAddress))->toString(false, true);
        $token0 = Bytes::bytesToHexString((new Builder())->writeAddress(new Address($user))->cell()->toBoc(false));
        $token1 = Bytes::bytesToHexString((new Builder())->writeAddress(new Address($tokenWallet))->cell()->toBoc(false));
        $result = json_decode($this->provider->runMethod($contractAddress, 'get_vault_address', [
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


    public function createWithdrawFeeBody(
        $queryId = null
    )
    {
        return (new Builder())
            ->writeUint(OpCodes::WITHDRAW_FEE, 32)
            ->writeUint($queryId ?? 0, 64)
            ->cell();
    }

    public function getWithdrawTxParams($gasAmount = null, $queryId = null)
    {
        return new TransactionParams($this->vaultAddress, Bytes::bytesToBase64($this->createWithdrawFeeBody($queryId)->toBoc(false)), $gasAmount ?? (new WithdrawFeeGas())->gasAmount);
    }

    /**
     * @throws DivisionByZeroException
     * @throws NumberFormatException
     */
    public function getVaultData()
    {
        if ($this->routerAddress == null)
            throw  new \Exception("Router address is required.");

        $result = json_decode($this->provider->runMethod($this->routerAddress, "get_router_data"), true);

        if ($result['success']) {
            $stacks = $result['stack'];
            if (count($stacks) != 4)
                throw new \Exception("Stack under/overflow");
            $ownerAddress = $this->readWalletAddress($stacks[0]);
            $tokenAddress = $this->readWalletAddress($stacks[1]);
            $routerAddress = $this->readWalletAddress($stacks[2]);
            $depositedAmount = BigInteger::of(hexdec($stacks[3][$stacks[3]['type']]));

            return new VaultData($ownerAddress, $tokenAddress, $routerAddress, $depositedAmount);
        } else {
            throw new \Exception("Contract getter failed.");
        }
    }


    /**
     * @throws CellException
     * @throws SliceException
     */
    public function readWalletAddress($item): Address
    {
        return (Cell::oneFromBoc($item[$item['type']])->beginParse()->loadAddress());
    }
}