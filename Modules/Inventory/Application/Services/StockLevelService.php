<?php
namespace Modules\Inventory\Application\Services;
use Illuminate\Support\Str;
use Modules\Inventory\Infrastructure\Models\StockLevelModel;
class StockLevelService
{
    public function increase(string $productId, string $locationId, string $qty, string $tenantId, ?string $variantId = null): void
    {
        $query = StockLevelModel::where('product_id', $productId)
            ->where('location_id', $locationId);
        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }
        $level = $query->lockForUpdate()->first();
        if ($level) {
            $level->qty = bcadd($level->qty, $qty, 8);
            $level->save();
        } else {
            StockLevelModel::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'location_id' => $locationId,
                'qty' => $qty,
                'reserved_qty' => '0.00000000',
            ]);
        }
    }

    public function decrease(string $productId, string $locationId, string $qty, string $tenantId, ?string $variantId = null): void
    {
        $query = StockLevelModel::where('product_id', $productId)
            ->where('location_id', $locationId);
        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }
        $level = $query->lockForUpdate()->firstOrFail();
        $newQty = bcsub($level->qty, $qty, 8);
        if (bccomp($newQty, '0', 8) < 0) {
            throw new \RuntimeException('Insufficient stock.');
        }
        $level->qty = $newQty;
        $level->save();
    }
}
