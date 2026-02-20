<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Barcode;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BarcodeService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Barcode::where('tenant_id', $tenantId);

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): Barcode
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                Barcode::where('tenant_id', $data['tenant_id'])->update(['is_default' => false]);
            }

            $barcode = Barcode::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Barcode::class,
                auditableId: $barcode->id,
                newValues: $data
            );

            return $barcode;
        });
    }

    public function update(string $id, array $data): Barcode
    {
        return DB::transaction(function () use ($id, $data) {
            $barcode = Barcode::findOrFail($id);
            $oldValues = $barcode->toArray();

            if (! empty($data['is_default'])) {
                Barcode::where('tenant_id', $barcode->tenant_id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $barcode->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Barcode::class,
                auditableId: $barcode->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $barcode->fresh();
        });
    }

    public function setDefault(string $id, string $tenantId): Barcode
    {
        return DB::transaction(function () use ($id, $tenantId) {
            Barcode::where('tenant_id', $tenantId)->update(['is_default' => false]);
            $barcode = Barcode::where('tenant_id', $tenantId)->findOrFail($id);
            $barcode->update(['is_default' => true]);

            return $barcode->fresh();
        });
    }

    public function delete(string $id): void
    {
        Barcode::findOrFail($id)->delete();
    }
}
