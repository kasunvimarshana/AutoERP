<?php

namespace App\Services\UOM;

class UOMService
{
    public function convert($productId, $fromUomId, $toUomId, $quantity)
    {
        if ($fromUomId == $toUomId) return $quantity;

        // direct conversion
        $conv = ProductUOMConversion::where('product_id', $productId)
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->first();
        if ($conv) return $quantity * $conv->factor;

        // reverse conversion
        $rev = ProductUOMConversion::where('product_id', $productId)
            ->where('from_uom_id', $toUomId)
            ->where('to_uom_id', $fromUomId)
            ->first();
        if ($rev) return $quantity / $rev->factor;

        // fallback to base UOM chain
        $product = Product::find($productId);
        $inBase = $this->toBase($productId, $fromUomId, $quantity);
        return $this->fromBase($productId, $toUomId, $inBase);
    }
}