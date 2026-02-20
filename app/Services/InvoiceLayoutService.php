<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\InvoiceLayout;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceLayoutService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = InvoiceLayout::where('tenant_id', $tenantId);

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): InvoiceLayout
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                InvoiceLayout::where('tenant_id', $data['tenant_id'])->update(['is_default' => false]);
            }

            $layout = InvoiceLayout::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: InvoiceLayout::class,
                auditableId: $layout->id,
                newValues: $data
            );

            return $layout;
        });
    }

    public function update(string $id, array $data): InvoiceLayout
    {
        return DB::transaction(function () use ($id, $data) {
            $layout = InvoiceLayout::findOrFail($id);
            $oldValues = $layout->toArray();

            if (! empty($data['is_default'])) {
                InvoiceLayout::where('tenant_id', $layout->tenant_id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $layout->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: InvoiceLayout::class,
                auditableId: $layout->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $layout->fresh();
        });
    }

    public function setDefault(string $id, string $tenantId): InvoiceLayout
    {
        return DB::transaction(function () use ($id, $tenantId) {
            InvoiceLayout::where('tenant_id', $tenantId)->update(['is_default' => false]);
            $layout = InvoiceLayout::where('tenant_id', $tenantId)->findOrFail($id);
            $layout->update(['is_default' => true]);

            return $layout->fresh();
        });
    }

    public function delete(string $id): void
    {
        InvoiceLayout::findOrFail($id)->delete();
    }
}
