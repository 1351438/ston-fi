<?php
require_once __DIR__ . '/../common.php';
/*
 * In this file we are going to swap some USDT with NOT
 * */

use Olifanton\Interop\Address;
use StonFi\contracts\dex\v1\Swap;
use StonFi\enums\Networks;
use StonFi\Init;

require_once __DIR__ . '/../common.php';

global $pizzatonJettonMaster, $usdtJettonMaster, $notJettonMaster, $userWalletAddr;
echo "-------------------------------SWAP JETTON TO JETTON----------------------" . PHP_EOL;

$init = new Init(Networks::MAINNET, "123");
$swap = new Swap($init);

// Simulate the swap first
$simulate = json_decode(
    $swap->simulate(
        from: $pizzatonJettonMaster,
        to: $notJettonMaster,
        units: \Olifanton\Interop\Units::toNano('1', 6), // 1 USDT -> ? NOT this must be nano and (depends on currency decimals)
        slippageTolerance: '0.001' // this means 1 percent
    ),
    true);
echo "Swap Simulate: " . PHP_EOL;
echo json_encode($simulate, 128);
echo PHP_EOL . PHP_EOL;
// Then if every thing seems fine and simulation didn't fail we are free to make swap

/// Define PTon Version 1 (PtonV1)
$proxyTon = new \StonFi\pTON\v1\PtonV1($init);

/// Create transaction params for swap 1 USDT With ? NOT
$transactionParams = $swap->JettonToJettonTxParams(
    userWalletAddress: new Address($userWalletAddr),
    offerJettonAddress: new Address($usdtJettonMaster),
    askJettonAddress: new Address($notJettonMaster),
    offerAmount: \Olifanton\Interop\Units::toNano('1', 6), // Decimals of USDT is 6 so we need to consider this in our offer amount
    minAskAmount: \Brick\Math\BigInteger::of($simulate['min_ask_units']),
);

echo "Transaction Parameters: " . PHP_EOL;
echo json_encode($transactionParams, 128);