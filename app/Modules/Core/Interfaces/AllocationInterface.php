<?php

namespace App\Modules\Core\Interfaces;

interface AllocationInterface
{
    public function allocate($product, $quantity, $orderId, $warehouseId = null, $preferences = []);
    public function release($reservation);
    public function getAvailableStock($product, $warehouseId = null, $strategy = 'fifo');
}