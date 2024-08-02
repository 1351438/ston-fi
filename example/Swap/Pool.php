<?php

require_once __DIR__ . '/../common.php';

global $usdtJettonMaster, $notJettonMaster, $pizzatonJettonMaster, $init, $userWalletAddr;

$pool = new \StonFi\contracts\dex\v1\Pool($init);
$proxyTon = new \StonFi\pTON\v1\PtonV1($init);

try {
    $USDtNotPool = $pool->getPoolAddressByJettonMinter($notJettonMaster, $usdtJettonMaster)->toString();
    $tonPTJPool = $pool->getPoolAddressByJettonMinter($pizzatonJettonMaster, $proxyTon->address)->toString();
    // Get Pool Address
    echo "Pool Address of USDt-NOT: " . $USDtNotPool . "\n";
    echo "Pool Address of TON-PTJ: " . $tonPTJPool . "\n";

    $pool->setPoolAddress($tonPTJPool);

    $poolData = $pool->getPoolData();
    echo "\n Pool Data: \n" . $poolData->toMap() . "\n";

    $userLpAccountAddress = $pool->getLpAccountAddress($userWalletAddr);
    echo "\n User Lp Address: " . $userLpAccountAddress . "\n";

    echo "\nGet expected tokens: " . $pool->getExpectedTokens(\Brick\Math\BigInteger::of(1), \Brick\Math\BigInteger::of(10));
    echo "\nGet Expected Liquidity: " . $pool->getExpectedLiquidity(\Brick\Math\BigInteger::of(150))->toMap();
    echo "\nGet Expected outputs: " . $pool->getExpectedOutputs(\Brick\Math\BigInteger::of(1), $pizzatonJettonMaster)->toMap();
} catch (Exception $e) {
    echo $e->getTraceAsString();
}