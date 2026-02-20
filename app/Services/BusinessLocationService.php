<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\BusinessLocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BusinessLocationService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BusinessLocation::where('tenant_id', $tenantId)
            ->with(['organization']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): BusinessLocation
    {
        return DB::transaction(function () use ($data) {
            $location = BusinessLocation::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: BusinessLocation::class,
                auditableId: $location->id,
                newValues: $data
            );

            return $location->fresh(['organization']);
        });
    }

    public function update(string $id, array $data): BusinessLocation
    {
        return DB::transaction(function () use ($id, $data) {
            $location = BusinessLocation::findOrFail($id);
            $oldValues = $location->toArray();
            $location->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: BusinessLocation::class,
                auditableId: $location->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $location->fresh(['organization']);
        });
    }

    public function delete(string $id): void
    {
        BusinessLocation::findOrFail($id)->delete();
    }
}
