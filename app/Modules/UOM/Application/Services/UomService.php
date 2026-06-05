<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomModel;

final class UomService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<UomModel>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = (string) ($filters['sort_by'] ?? 'uom_code');
        $sortDirection = (string) ($filters['sort_direction'] ?? 'asc');
        $perPage = min(
            (int) ($filters['per_page'] ?? config('uom.pagination.default_per_page', 20)),
            (int) config('uom.pagination.max_per_page', 200),
        );

        return UomModel::query()
            ->select([
                'id',
                'organization_unit_id',
                'uom_code',
                'name',
                'symbol',
                'decimal_precision',
                'is_base',
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
                        ->where('uom_code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('symbol', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    /**
     * @return Collection<int, UomModel>
     */
    public function lookup(): Collection
    {
        return UomModel::query()
            ->select(['id', 'uom_code', 'name', 'symbol'])
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'active')
            ->orderBy('uom_code')
            ->get();
    }

    public function find(int $id): UomModel
    {
        return UomModel::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): UomModel
    {
        $attributes = $this->attributes($payload, $this->tenantId());
        $attributes['created_by'] = $this->currentUser->currentUserId();

        return UomModel::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $id, array $payload): UomModel
    {
        $uom = $this->find($id);
        $attributes = $this->attributes($payload, (int) $uom->tenant_id, false);
        $attributes['updated_by'] = $this->currentUser->currentUserId();
        $attributes['row_version'] = ((int) $uom->row_version) + 1;

        $uom->fill($attributes)->save();

        return $uom->refresh();
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
            'uom_code',
            'name',
            'symbol',
            'decimal_precision',
            'is_base',
            'status',
            'notes',
        ]);

        if ($creating) {
            $attributes['tenant_id'] = $tenantId;
            $attributes['organization_unit_id'] = $organizationUnitId;
            $attributes['decimal_precision'] ??= 2;
            $attributes['is_base'] ??= false;
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
