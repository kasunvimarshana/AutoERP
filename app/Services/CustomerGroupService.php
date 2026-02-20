<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\CustomerGroup;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerGroupService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CustomerGroup::where('tenant_id', $tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): CustomerGroup
    {
        return DB::transaction(function () use ($data) {
            $customerGroup = CustomerGroup::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: CustomerGroup::class,
                auditableId: $customerGroup->id,
                newValues: $data
            );

            return $customerGroup;
        });
    }

    public function update(string $id, array $data): CustomerGroup
    {
        return DB::transaction(function () use ($id, $data) {
            $customerGroup = CustomerGroup::findOrFail($id);
            $oldValues = $customerGroup->toArray();
            $customerGroup->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: CustomerGroup::class,
                auditableId: $customerGroup->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $customerGroup->fresh();
        });
    }

    public function delete(string $id): void
    {
        CustomerGroup::findOrFail($id)->delete();
    }
}
