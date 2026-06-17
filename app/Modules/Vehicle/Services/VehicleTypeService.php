<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleTypeData;
use Modules\Vehicle\Models\VehicleType;
use Modules\Vehicle\Validators\VehicleValidationService;

final class VehicleTypeService
{
    public function __construct(private readonly VehicleValidationService $validator) {}
    public function create(VehicleTypeData $data): VehicleType { $this->validate($data); $this->assertCodeUnique($data->tenantId, $data->code); return VehicleType::query()->create(['tenant_id' => $data->tenantId, 'organization_unit_id' => $data->organizationUnitId, 'code' => $data->code, 'name' => $data->name, 'description' => $data->description, 'is_active' => $data->isActive, 'sort_order' => $data->sortOrder]); }
    public function update(VehicleType $type, VehicleTypeData $data): VehicleType { if ((int) $type->tenant_id !== $data->tenantId) { throw new InvalidArgumentException('Vehicle type belongs to a different tenant.'); } $this->validate($data); $this->assertCodeUnique($data->tenantId, $data->code, (int) $type->getKey()); $type->fill(['organization_unit_id' => $data->organizationUnitId, 'code' => $data->code, 'name' => $data->name, 'description' => $data->description, 'is_active' => $data->isActive, 'sort_order' => $data->sortOrder])->save(); return $type->refresh(); }
    public function delete(VehicleType $type): void { if ($type->vehicles()->exists()) { throw new InvalidArgumentException('Vehicle type cannot be deleted while vehicles reference it.'); } $type->delete(); }
    public function find(int $id, int $tenantId, ?int $organizationUnitId): VehicleType { return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id); }
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator { return $this->criteria($this->baseQuery($tenantId, $organizationUnitId), $criteria)->orderBy('sort_order')->orderBy('name')->paginate($perPage); }
    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator { $criteria['is_active'] = true; return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50)); }
    private function validate(VehicleTypeData $data): void { if (trim($data->code) === '' || trim($data->name) === '') { throw new InvalidArgumentException('Vehicle type code and name are required.'); } if ($data->sortOrder < 0) { throw new InvalidArgumentException('Vehicle type sort order cannot be negative.'); } $this->validator->assertOrganizationUsable($data->tenantId, $data->organizationUnitId); }
    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder { return VehicleType::query()->where('tenant_id', $tenantId)->when($organizationUnitId !== null, fn (Builder $query) => $query->where(fn (Builder $scope) => $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId))); }
    private function criteria(Builder $query, array $criteria): Builder { $search = trim((string) ($criteria['search'] ?? '')); if ($search !== '') { $query->where(fn (Builder $scope) => $scope->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")); } if (array_key_exists('is_active', $criteria) && $criteria['is_active'] !== null && $criteria['is_active'] !== '') { $query->where('is_active', $criteria['is_active']); } return $query; }
    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void { $query = VehicleType::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code); if ($ignoreId !== null) { $query->whereKeyNot($ignoreId); } if ($query->exists()) { throw new InvalidArgumentException('Vehicle type code already exists for this tenant.'); } }
}
