<?php

namespace App\Interfaces;

interface PlatformInterface
{
    public function updateStock($sku, $quantity);
}
