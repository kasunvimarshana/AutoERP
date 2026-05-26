<?php

declare(strict_types=1);

namespace Modules\Sequence\Domain\Services;

use Illuminate\Support\Facades\DB;

final class SequenceService
{
    public function next(string $documentType, int $tenantId, ?int $organizationUnitId = null): string
    {
        $period = now()->format('Y');
        $sequence = DB::table('sequences')
            ->where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->where('period_value', $period)
            ->when($organizationUnitId === null, static fn ($query) => $query->whereNull('organization_unit_id'), static fn ($query) => $query->where('organization_unit_id', $organizationUnitId))
            ->lockForUpdate()
            ->first();

        $prefix = strtoupper(str_replace('_', '-', $documentType)) . '-';

        if ($sequence === null) {
            DB::table('sequences')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'document_type' => $documentType,
                'prefix' => $prefix,
                'suffix' => '',
                'padding' => 5,
                'next_number' => 2,
                'period_type' => 'yearly',
                'period_value' => $period,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $prefix . '00001';
        }

        DB::table('sequences')->where('id', (int) $sequence->id)->update([
            'next_number' => (int) $sequence->next_number + 1,
            'row_version' => (int) $sequence->row_version + 1,
            'updated_at' => now(),
        ]);

        return (string) $sequence->prefix
            . str_pad((string) $sequence->next_number, (int) $sequence->padding, '0', STR_PAD_LEFT)
            . (string) $sequence->suffix;
    }
}
