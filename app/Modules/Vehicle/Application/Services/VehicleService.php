<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;

final class VehicleService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<VehicleModel>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = (string) ($filters['sort_by'] ?? 'vehicle_code');
        $sortDirection = (string) ($filters['sort_direction'] ?? 'asc');
        $perPage = min(
            (int) ($filters['per_page'] ?? config('vehicle.pagination.default_per_page', 20)),
            (int) config('vehicle.pagination.max_per_page', 200),
        );

        return VehicleModel::query()
            ->select([
                'id',
                'organization_unit_id',
                'vehicle_code',
                'registration_number',
                'chassis_number',
                'engine_number',
                'make',
                'model',
                'year',
                'color',
                'vehicle_type',
                'fuel_type',
                'transmission_type',
                'ownership_type',
                'status',
                'created_at',
                'updated_at',
            ])
            ->where('tenant_id', $this->tenantId())
            ->when(
                isset($filters['organization_unit_id']),
                fn (Builder $query): Builder => $query->where(
                    'organization_unit_id',
                    (int) $filters['organization_unit_id'],
                ),
            )
            ->when(
                isset($filters['status']),
                fn (Builder $query): Builder => $query->where('status', (string) $filters['status']),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('vehicle_code', 'like', '%'.$search.'%')
                        ->orWhere('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('chassis_number', 'like', '%'.$search.'%')
                        ->orWhere('engine_number', 'like', '%'.$search.'%')
                        ->orWhere('make', 'like', '%'.$search.'%')
                        ->orWhere('model', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function find(int $id): VehicleModel
    {
        return VehicleModel::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): VehicleModel
    {
        $attributes = $this->attributes($payload, $this->tenantId());
        $attributes['created_by'] = $this->currentUser->currentUserId();

        return VehicleModel::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $id, array $payload): VehicleModel
    {
        $vehicle = $this->find($id);
        $attributes = $this->attributes($payload, (int) $vehicle->tenant_id, false);
        $attributes['updated_by'] = $this->currentUser->currentUserId();
        $attributes['row_version'] = ((int) $vehicle->row_version) + 1;

        $vehicle->fill($attributes)->save();

        return $vehicle->refresh();
    }

    public function delete(int $id): void
    {
        $this->find($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function attributes(array $payload, int $tenantId, bool $creating = true): array
    {
        $organizationUnitId = array_key_exists('organization_unit_id', $payload)
            ? $payload['organization_unit_id']
            : ($creating ? $this->currentOrganizationUnit->currentOrganizationUnitId() : null);

        if ($organizationUnitId !== null) {
            $belongsToTenant = DB::table('organization_units')
                ->where('id', (int) $organizationUnitId)
                ->where('tenant_id', $tenantId)
                ->exists();

            if (! $belongsToTenant) {
                throw ValidationException::withMessages([
                    'organization_unit_id' => ['The selected organization unit does not belong to the active tenant.'],
                ]);
            }
        }

        $attributes = Arr::only($payload, [
            'organization_unit_id',
            'vehicle_code',
            'registration_number',
            'chassis_number',
            'engine_number',
            'make',
            'model',
            'year',
            'color',
            'vehicle_type',
            'fuel_type',
            'transmission_type',
            'ownership_type',
            'status',
            'notes',
        ]);

        if ($creating) {
            $attributes['tenant_id'] = $tenantId;
            $attributes['organization_unit_id'] = $organizationUnitId;
            $attributes['status'] ??= 'active';
        } elseif (array_key_exists('organization_unit_id', $payload)) {
            $attributes['organization_unit_id'] = $organizationUnitId;
        }

        return $attributes;
    }

    private function tenantId(): int
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null) {
            throw ValidationException::withMessages([
                'tenant_id' => ['Tenant context is required.'],
            ]);
        }

        return $tenantId;
    }
}
