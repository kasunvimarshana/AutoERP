<?php

namespace App\Modules\Core\Interfaces;

interface ValuationInterface
{
    public function calculateCost($product, $quantity, $warehouseId = null);
    public function addLayer($transaction, $unitCost);
    public function consumeLayers($product, $quantity, $warehouseId = null, $strategy = null);
}