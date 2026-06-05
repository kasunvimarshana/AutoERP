<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

final class SupplierService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<SupplierModel>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $tenantId = $this->tenantId();
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = (string) ($filters['sort_by'] ?? 'supplier_name');
        $sortDirection = (string) ($filters['sort_direction'] ?? 'asc');
        $perPage = min(
            (int) ($filters['per_page'] ?? config('supplier.pagination.default_per_page', 20)),
            (int) config('supplier.pagination.max_per_page', 200),
        );

        return SupplierModel::query()
            ->select([
                'id',
                'tenant_id',
                'organization_unit_id',
                'supplier_code',
                'supplier_name',
                'display_name',
                'email',
                'phone',
                'mobile',
                'credit_limit',
                'payment_terms_days',
                'status',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->where('tenant_id', $tenantId)
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
                        ->where('supplier_code', 'like', '%'.$search.'%')
                        ->orWhere('supplier_name', 'like', '%'.$search.'%')
                        ->orWhere('display_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function find(int $id): SupplierModel
    {
        return SupplierModel::query()
            ->with('primaryAddress')
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): SupplierModel
    {
        return DB::transaction(function () use ($payload): SupplierModel {
            $tenantId = $this->tenantId();
            $address = Arr::pull($payload, 'address');
            $attributes = $this->attributes($payload, $tenantId);
            $attributes['created_by'] = $this->currentUser->currentUserId();

            $supplier = SupplierModel::query()->create($attributes);
            $this->syncAddress($supplier, is_array($address) ? $address : null, $address === null);
            $this->recordStatus($supplier, null, $supplier->status);

            return $supplier->load('primaryAddress');
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $id, array $payload): SupplierModel
    {
        return DB::transaction(function () use ($id, $payload): SupplierModel {
            $supplier = SupplierModel::query()
                ->where('tenant_id', $this->tenantId())
                ->lockForUpdate()
                ->findOrFail($id);
            $hasAddress = array_key_exists('address', $payload);
            $address = Arr::pull($payload, 'address');
            $previousStatus = (string) $supplier->status;
            $attributes = $this->attributes($payload, (int) $supplier->tenant_id, false);
            $attributes['updated_by'] = $this->currentUser->currentUserId();
            $attributes['row_version'] = ((int) $supplier->row_version) + 1;

            $supplier->fill($attributes)->save();

            if ($hasAddress) {
                $this->syncAddress($supplier, is_array($address) ? $address : null, $address === null);
            }

            if ($previousStatus !== (string) $supplier->status) {
                $this->recordStatus($supplier, $previousStatus, (string) $supplier->status);
            }

            return $supplier->load('primaryAddress');
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $supplier = SupplierModel::query()
                ->where('tenant_id', $this->tenantId())
                ->lockForUpdate()
                ->findOrFail($id);

            SupplierAddressModel::query()
                ->where('tenant_id', (int) $supplier->tenant_id)
                ->where('supplier_id', $supplier->getKey())
                ->delete();

            $supplier->delete();
        });
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
            'supplier_code',
            'display_name',
            'email',
            'phone',
            'mobile',
            'tax_number',
            'vat_number',
            'credit_limit',
            'payment_terms_days',
            'status',
            'notes',
        ]);

        if (array_key_exists('name', $payload)) {
            $attributes['supplier_name'] = $payload['name'];
        }

        if ($creating) {
            $attributes['tenant_id'] = $tenantId;
            $attributes['organization_unit_id'] = $organizationUnitId;
            $attributes['supplier_type'] = 'business';
            $attributes['credit_limit'] ??= 0;
            $attributes['payment_terms_days'] ??= 0;
            $attributes['status'] ??= 'active';
        } elseif (array_key_exists('organization_unit_id', $payload)) {
            $attributes['organization_unit_id'] = $organizationUnitId;
        }

        if (array_key_exists('status', $attributes)) {
            $attributes['is_active'] = $attributes['status'] === 'active';
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>|null  $address
     */
    private function syncAddress(SupplierModel $supplier, ?array $address, bool $delete): void
    {
        $existing = SupplierAddressModel::query()
            ->where('tenant_id', (int) $supplier->tenant_id)
            ->where('supplier_id', $supplier->getKey())
            ->where('is_default', true)
            ->first();

        if ($delete) {
            $existing?->delete();

            return;
        }

        if ($address === null) {
            return;
        }

        $attributes = [
            'tenant_id' => (int) $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
            'supplier_id' => $supplier->getKey(),
            'type' => 'billing',
            'label' => $address['label'] ?? 'Primary',
            'address_line1' => $address['address_line_1'],
            'address_line2' => $address['address_line_2'] ?? null,
            'city' => $address['city'],
            'state' => $address['state_province'] ?? null,
            'postal_code' => $address['postal_code'],
            'country_name' => $address['country_name'] ?? null,
            'is_default' => true,
            'is_default_billing' => true,
            'is_default_shipping' => true,
            'is_active' => true,
            'updated_by' => $this->currentUser->currentUserId(),
        ];

        if ($existing === null) {
            $attributes['created_by'] = $this->currentUser->currentUserId();
            SupplierAddressModel::query()->create($attributes);

            return;
        }

        $attributes['row_version'] = ((int) $existing->row_version) + 1;
        $existing->fill($attributes)->save();
    }

    private function recordStatus(SupplierModel $supplier, ?string $fromStatus, string $toStatus): void
    {
        DB::table('supplier_status_histories')->insert([
            'tenant_id' => (int) $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
            'supplier_id' => $supplier->getKey(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $this->currentUser->currentUserId(),
            'changed_at' => now(),
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
