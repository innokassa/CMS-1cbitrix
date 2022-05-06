<?php

namespace Innokassa\Fiscal\Impl;

use Innokassa\MDK\Entities\ReceiptId\ReceiptIdFactoryMeta;

class ReceiptIdFactoryMetaConcrete extends ReceiptIdFactoryMeta
{
    protected function getEngine(): string
    {
        return '1cb';
    }
}
