<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;

final class InventoryNumberService
{
    public function next(int $tenantId, string $prefix): string
    {
        return DB::transaction(function () use ($tenantId, $prefix): string {
            $date = now()->format('Ymd');
            $sequenceKey = $prefix.':'.$date;
            $timestamp = now();

            DB::table('inventory_number_sequences')->upsert(
                [[
                    'tenant_id' => $tenantId,
                    'sequence_key' => $sequenceKey,
                    'last_number' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]],
                ['tenant_id', 'sequence_key'],
                ['updated_at'],
            );

            $sequence = DB::table('inventory_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->first();
            $next = ((int) $sequence->last_number) + 1;

            DB::table('inventory_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('id', $sequence->id)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return $prefix.'-'.$date.'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        });
    }
}
