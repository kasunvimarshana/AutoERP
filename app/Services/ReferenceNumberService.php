<?php

namespace App\Services;

use App\Models\ReferenceCount;
use Illuminate\Support\Facades\DB;

class ReferenceNumberService
{
    /**
     * Generate the next reference number for a given type and location.
     * Uses pessimistic locking to prevent race conditions.
     */
    public function generate(
        string $tenantId,
        string $refType,
        ?string $businessLocationId = null,
        string $prefix = '',
        int $padLength = 6
    ): string {
        return DB::transaction(function () use ($tenantId, $refType, $businessLocationId, $prefix, $padLength) {
            $record = ReferenceCount::where('tenant_id', $tenantId)
                ->where('ref_type', $refType)
                ->where(function ($q) use ($businessLocationId) {
                    if ($businessLocationId !== null) {
                        $q->where('business_location_id', $businessLocationId);
                    } else {
                        $q->whereNull('business_location_id');
                    }
                })
                ->lockForUpdate()
                ->first();

            if (! $record) {
                $record = ReferenceCount::create([
                    'tenant_id' => $tenantId,
                    'ref_type' => $refType,
                    'business_location_id' => $businessLocationId,
                    'count' => 0,
                ]);
            }

            $nextCount = $record->count + 1;
            $record->update(['count' => $nextCount]);

            $suffix = str_pad((string) $nextCount, $padLength, '0', STR_PAD_LEFT);

            return $prefix.$suffix;
        });
    }

    /**
     * Generate a POS transaction reference number.
     */
    public function posTransaction(string $tenantId, ?string $businessLocationId = null): string
    {
        return $this->generate($tenantId, 'sell', $businessLocationId, 'POS-');
    }

    /**
     * Generate a Purchase Order reference number.
     */
    public function purchaseOrder(string $tenantId, ?string $businessLocationId = null): string
    {
        return $this->generate($tenantId, 'purchase', $businessLocationId, 'PO-');
    }

    /**
     * Generate a POS Return reference number.
     */
    public function posReturn(string $tenantId, ?string $businessLocationId = null): string
    {
        return $this->generate($tenantId, 'sell_return', $businessLocationId, 'RET-');
    }

    /**
     * Generate a Stock Adjustment reference number.
     */
    public function stockAdjustment(string $tenantId, ?string $businessLocationId = null): string
    {
        return $this->generate($tenantId, 'stock_adjustment', $businessLocationId, 'ADJ-');
    }

    /**
     * Generate an Expense reference number.
     */
    public function expense(string $tenantId, ?string $businessLocationId = null): string
    {
        return $this->generate($tenantId, 'expense', $businessLocationId, 'EXP-');
    }
}
