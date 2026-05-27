<?php

namespace Modules\Document\Application\Services;

use Illuminate\Support\Facades\DB;

class SequenceService
{
    public function nextNumber(
        int $tenantId,
        ?int $organizationUnitId,
        string $documentType,
        ?string $date = null
    ): string {
        $date = $date ?? now()->toDateString();
        $periodValue = date('Y', strtotime($date));

        return DB::transaction(function () use ($tenantId, $organizationUnitId, $documentType, $periodValue): string {
            $sequence = DB::table('sequences')
                ->where('tenant_id', $tenantId)
                ->where('organization_unit_id', $organizationUnitId)
                ->where('document_type', $documentType)
                ->where('period_value', $periodValue)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $id = DB::table('sequences')->insertGetId([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'document_type' => $documentType,
                    'prefix' => strtoupper(substr($documentType, 0, 3)) . '-',
                    'suffix' => '',
                    'padding' => 5,
                    'next_number' => 2,
                    'period_value' => $periodValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sequence = DB::table('sequences')->find($id);
                $currentNumber = 1;
            } else {
                $currentNumber = (int) $sequence->next_number;

                DB::table('sequences')
                    ->where('id', $sequence->id)
                    ->update([
                        'next_number' => $currentNumber + 1,
                        'updated_at' => now(),
                    ]);
            }

            return ($sequence->prefix ?? '')
                . str_pad((string) $currentNumber, (int) ($sequence->padding ?? 5), '0', STR_PAD_LEFT)
                . ($sequence->suffix ?? '');
        });
    }
}
