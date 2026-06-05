<?php

declare(strict_types=1);

namespace Modules\Item\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;

final class ItemService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<ItemModel>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = (string) ($filters['sort_by'] ?? 'item_code');
        $sortDirection = (string) ($filters['sort_direction'] ?? 'asc');
        $perPage = min(
            (int) ($filters['per_page'] ?? config('item.pagination.default_per_page', 20)),
            (int) config('item.pagination.max_per_page', 200),
        );

        return ItemModel::query()
            ->select([
                'id',
                'organization_unit_id',
                'item_code',
                'name',
                'display_name',
                'item_type',
                'base_uom_id',
                'sku',
                'barcode',
                'track_inventory',
                'is_stock_item',
                'is_service_item',
                'cost_price',
                'sales_price',
                'reorder_level',
                'reorder_quantity',
                'status',
                'created_at',
                'updated_at',
            ])
            ->with('baseUom:id,uom_code,name,symbol')
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
                        ->where('item_code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('display_name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function find(int $id): ItemModel
    {
        return ItemModel::query()
            ->with([
                'baseUom:id,uom_code,name,symbol',
                'purchaseUom:id,uom_code,name,symbol',
                'salesUom:id,uom_code,name,symbol',
            ])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): ItemModel
    {
        $attributes = $this->attributes($payload, $this->tenantId());
        $attributes['created_by'] = $this->currentUser->currentUserId();

        return ItemModel::query()->create($attributes)->load([
            'baseUom:id,uom_code,name,symbol',
            'purchaseUom:id,uom_code,name,symbol',
            'salesUom:id,uom_code,name,symbol',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $id, array $payload): ItemModel
    {
        $item = ItemModel::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);
        $attributes = $this->attributes($payload, (int) $item->tenant_id, false);
        $attributes['updated_by'] = $this->currentUser->currentUserId();
        $attributes['row_version'] = ((int) $item->row_version) + 1;

        $item->fill($attributes)->save();

        return $item->refresh()->load([
            'baseUom:id,uom_code,name,symbol',
            'purchaseUom:id,uom_code,name,symbol',
            'salesUom:id,uom_code,name,symbol',
        ]);
    }

    public function delete(int $id): void
    {
        ItemModel::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id)
            ->delete();
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
            'item_code',
            'name',
            'display_name',
            'item_type',
            'base_uom_id',
            'purchase_uom_id',
            'sales_uom_id',
            'sku',
            'barcode',
            'description',
            'track_inventory',
            'is_stock_item',
            'is_service_item',
            'cost_price',
            'sales_price',
            'reorder_level',
            'reorder_quantity',
            'status',
            'notes',
        ]);

        if ($creating) {
            $attributes['tenant_id'] = $tenantId;
            $attributes['organization_unit_id'] = $organizationUnitId;
            $attributes['track_inventory'] ??= true;
            $attributes['is_stock_item'] ??= true;
            $attributes['is_service_item'] ??= false;
            $attributes['cost_price'] ??= 0;
            $attributes['sales_price'] ??= 0;
            $attributes['reorder_level'] ??= 0;
            $attributes['reorder_quantity'] ??= 0;
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
