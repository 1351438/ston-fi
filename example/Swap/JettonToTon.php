<?php
/*
 * In this file we are going to swap some PTJ (PizzaTon Jetton) with TON
 * */

use Olifanton\Interop\Address;

use StonFi\contracts\dex\v1\Swap;
use StonFi\enums\Networks;
use StonFi\Init;

require_once __DIR__ . '/../common.php';

global $pizzatonJettonMaster, $userWalletAddr, $init;
echo "-------------------------------SWAP JETTON TO TON----------------------" . PHP_EOL;

$swap = new Swap($init);

// Simulate the swap first
$simulate = json_decode(
    $swap->simulate(
        from: $pizzatonJettonMaster,
        to: $init->getTonAddress(),
        units: \Olifanton\Interop\Units::toNano('100'), // this must be nano and (depends on currency decimals)
        slippageTolerance: '0.001' // this means 1 percent
    ),
    true);
echo "Swap Simulate: " . PHP_EOL;
echo json_encode($simulate, 128);
echo PHP_EOL . PHP_EOL;
// Then if every thing seems fine and simulation didn't fail we are free to make swap

/// Define PTon Version 1 (PtonV1)
$proxyTon = new \StonFi\pTON\v1\PtonV1($init);

/// Create transaction params for swap 100 PTJ
$transactionParams = $swap->JettonToTonTxParams(
    proxyTon: $proxyTon,
    userWalletAddress: new Address($userWalletAddr),
    offerJettonAddress: new Address($pizzatonJettonMaster),
    offerAmount: \Olifanton\Interop\Units::toNano('100'),
    minAskAmount: \Brick\Math\BigInteger::of($simulate['min_ask_units'])
);

echo "Transaction Parameters: " . PHP_EOL;
echo json_encode($transactionParams, 128);