<?php

namespace StonFi\const;

class OpCodes
{
    const SWAP = 0x25938561;
    const CROSS_SWAP = 0xffffffef;
    const PROVIDE_LP = 0xfcf9e58f;
    const CROSS_PROVIDE_LP = 0xfffffeff;
    const DIRECT_ADD_LIQUIDITY = 0x4cf82803;
    const REFUND_ME = 0x0bf3f447;
    const RESET_GAS = 0x42a0fb43;
    const COLLECT_FEES = 0x1fcb7d3d;
    const BURN = 0x595f07bc;
    const WITHDRAW_FEE = 0x45ed2dc7;

    // PTon
    const TON_TRANSFER = 0x01f3835d;
}