<?php

use StonFi\enums\Networks;
use StonFi\Init;

header("Content-type: application/json");

require_once __DIR__ . "/../vendor/autoload.php";

echo ("------------------------START DEFINE-------------------------" . PHP_EOL.PHP_EOL    );

$pizzatonJettonMaster = 'EQAgotSkX06MIW-A0ni5yKqeNlwc3nASnbO1dwGo-kwpg2Zg';
$usdtJettonMaster = 'EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs';
$notJettonMaster = 'EQAvlWFDxGF2lXm67y4yzC17wYKD9A0guwPkMs1gOsM__NOT';
$userWalletAddr = 'UQBglMTG79v4lv6j0CyhJSKaB8vNr704qUgcyA0CkUtGEZ7q';

echo "PTJ Master: " . $pizzatonJettonMaster . PHP_EOL;
echo "USDT Master: " . $usdtJettonMaster . PHP_EOL;
echo "NOT Master: " . $notJettonMaster . PHP_EOL;
echo "User Wallet Address: " . $userWalletAddr . PHP_EOL;

echo (PHP_EOL."-------------------------END DEFINE--------------------------" . PHP_EOL.PHP_EOL    );

$init = new Init(Networks::MAINNET, "TONCENTER_API_KEY");