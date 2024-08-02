<?php

require_once __DIR__ . '/../common.php';

global $usdtJettonMaster, $notJettonMaster, $init, $userWalletAddr;

$lp = new \StonFi\contracts\dex\v1\ProvideLiquidity($init);
$pool = new \StonFi\contracts\dex\v1\Pool($init);
$proxyTon = new \StonFi\pTON\v1\PtonV1($init);

try {
    // Get Lp Address
    echo "Pool Address of USDt-NOT: " . $pool->getPoolAddressByJettonMinter($notJettonMaster, $usdtJettonMaster)->toString() . "\n";
    echo "Pool Address of TON-USDt: " . $pool->getPoolAddressByJettonMinter($proxyTon->address, $usdtJettonMaster)->toString() . "\n";
    echo "Pool Address of TON-NOT: " . $pool->getPoolAddressByJettonMinter($proxyTon->address, $notJettonMaster)->toString() . "\n";


    echo "\nJetton Transaction Params: \n";
    echo json_encode($lp->JettonTxParams(new \Olifanton\Interop\Address($userWalletAddr), new \Olifanton\Interop\Address($usdtJettonMaster), new \Olifanton\Interop\Address($notJettonMaster), \Brick\Math\BigInteger::of("10"), \Brick\Math\BigInteger::of("1000")));
    echo "\n\nTon Transaction Params: \n ";
    echo json_encode($lp->TonTxParams(new \Olifanton\Interop\Address($userWalletAddr), $proxyTon, new \Olifanton\Interop\Address($notJettonMaster), \Brick\Math\BigInteger::of("10"), \Brick\Math\BigInteger::of("1000")));
} catch (Exception $e) {
    echo $e->getTraceAsString();
}