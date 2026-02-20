<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\TaxRate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaxRateService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TaxRate::where('tenant_id', $tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): TaxRate
    {
        return DB::transaction(function () use ($data) {
            $taxRate = TaxRate::create($data);

            if (! empty($data['sub_tax_ids']) && $data['type'] === 'group') {
                $taxRate->subTaxes()->sync($data['sub_tax_ids']);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: TaxRate::class,
                auditableId: $taxRate->id,
                newValues: $data
            );

            return $taxRate->fresh(['subTaxes']);
        });
    }

    public function update(string $id, array $data): TaxRate
    {
        return DB::transaction(function () use ($id, $data) {
            $taxRate = TaxRate::findOrFail($id);
            $oldValues = $taxRate->toArray();
            $taxRate->update($data);

            if (isset($data['sub_tax_ids']) && $taxRate->type === 'group') {
                $taxRate->subTaxes()->sync($data['sub_tax_ids']);
            }

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: TaxRate::class,
                auditableId: $taxRate->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $taxRate->fresh(['subTaxes']);
        });
    }

    public function delete(string $id): void
    {
        $taxRate = TaxRate::findOrFail($id);
        $taxRate->subTaxes()->detach();
        $taxRate->delete();
    }
}
