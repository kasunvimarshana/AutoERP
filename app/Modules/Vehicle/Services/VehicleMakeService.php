<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleMakeData;
use Modules\Vehicle\Models\VehicleMake;
use Modules\Vehicle\Validators\VehicleValidationService;

final class VehicleMakeService
{
    public function __construct(private readonly VehicleValidationService $validator) {}

    public function create(VehicleMakeData $data): VehicleMake
    {
        $this->validate($data->tenantId, $data->organizationUnitId, $data->code, $data->name);
        $this->assertCodeUnique($data->tenantId, $data->code);
        return VehicleMake::query()->create(['tenant_id' => $data->tenantId, 'organization_unit_id' => $data->organizationUnitId, 'code' => $data->code, 'name' => $data->name, 'description' => $data->description, 'is_active' => $data->isActive]);
    }

    public function update(VehicleMake $make, VehicleMakeData $data): VehicleMake
    {
        if ((int) $make->tenant_id !== $data->tenantId) { throw new InvalidArgumentException('Vehicle make belongs to a different tenant.'); }
        $this->validate($data->tenantId, $data->organizationUnitId, $data->code, $data->name);
        $this->assertCodeUnique($data->tenantId, $data->code, (int) $make->getKey());
        $make->fill(['organization_unit_id' => $data->organizationUnitId, 'code' => $data->code, 'name' => $data->name, 'description' => $data->description, 'is_active' => $data->isActive])->save();
        return $make->refresh();
    }

    public function delete(VehicleMake $make): void
    {
        if ($make->models()->exists()) { throw new InvalidArgumentException('Vehicle make cannot be deleted while models reference it.'); }
        if ($make->vehicles()->exists()) { throw new InvalidArgumentException('Vehicle make cannot be deleted while vehicles reference it.'); }
        $make->delete();
    }
    public function find(int $id, int $tenantId, ?int $organizationUnitId): VehicleMake { return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id); }
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator { return $this->criteria($this->baseQuery($tenantId, $organizationUnitId), $criteria)->orderBy('name')->paginate($perPage); }
    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator { $criteria['is_active'] = true; return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50)); }

    private function validate(int $tenantId, ?int $organizationUnitId, string $code, string $name): void { if (trim($code) === '' || trim($name) === '') { throw new InvalidArgumentException('Vehicle make code and name are required.'); } $this->validator->assertOrganizationUsable($tenantId, $organizationUnitId); }
    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder { return VehicleMake::query()->where('tenant_id', $tenantId)->when($organizationUnitId !== null, fn (Builder $query) => $query->where(fn (Builder $scope) => $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId))); }
    private function criteria(Builder $query, array $criteria): Builder { $search = trim((string) ($criteria['search'] ?? '')); if ($search !== '') { $query->where(fn (Builder $scope) => $scope->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")); } if (array_key_exists('is_active', $criteria) && $criteria['is_active'] !== null && $criteria['is_active'] !== '') { $query->where('is_active', $criteria['is_active']); } return $query; }
    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void { $query = VehicleMake::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code); if ($ignoreId !== null) { $query->whereKeyNot($ignoreId); } if ($query->exists()) { throw new InvalidArgumentException('Vehicle make code already exists for this tenant.'); } }
}
