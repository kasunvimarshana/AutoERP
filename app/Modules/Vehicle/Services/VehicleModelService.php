<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleModelData;
use Modules\Vehicle\Models\VehicleModel;
use Modules\Vehicle\Validators\VehicleValidationService;

final class VehicleModelService
{
    public function __construct(private readonly VehicleValidationService $validator) {}

    public function create(VehicleModelData $data): VehicleModel
    {
        $this->validate($data);
        $this->assertCodeUnique($data->tenantId, $data->vehicleMakeId, $data->code);
        return VehicleModel::query()->create(['tenant_id' => $data->tenantId, 'organization_unit_id' => $data->organizationUnitId, 'vehicle_make_id' => $data->vehicleMakeId, 'code' => $data->code, 'name' => $data->name, 'year_from' => $data->yearFrom, 'year_to' => $data->yearTo, 'description' => $data->description, 'is_active' => $data->isActive]);
    }

    public function update(VehicleModel $model, VehicleModelData $data): VehicleModel
    {
        if ((int) $model->tenant_id !== $data->tenantId) { throw new InvalidArgumentException('Vehicle model belongs to a different tenant.'); }
        $this->validate($data);
        $this->assertCodeUnique($data->tenantId, $data->vehicleMakeId, $data->code, (int) $model->getKey());
        $model->fill(['organization_unit_id' => $data->organizationUnitId, 'vehicle_make_id' => $data->vehicleMakeId, 'code' => $data->code, 'name' => $data->name, 'year_from' => $data->yearFrom, 'year_to' => $data->yearTo, 'description' => $data->description, 'is_active' => $data->isActive])->save();
        return $model->refresh()->load('make');
    }

    public function delete(VehicleModel $model): void { $model->delete(); }
    public function find(int $id, int $tenantId, ?int $organizationUnitId): VehicleModel { return $this->baseQuery($tenantId, $organizationUnitId)->with('make')->findOrFail($id); }
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator { return $this->criteria($this->baseQuery($tenantId, $organizationUnitId)->with('make'), $criteria)->orderBy('name')->paginate($perPage); }
    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator { $criteria['is_active'] = true; return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50)); }

    private function validate(VehicleModelData $data): void { if (trim($data->code) === '' || trim($data->name) === '') { throw new InvalidArgumentException('Vehicle model code and name are required.'); } $this->validator->assertOrganizationUsable($data->tenantId, $data->organizationUnitId); $this->validator->validateModelData($data); }
    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder { return VehicleModel::query()->where('tenant_id', $tenantId)->when($organizationUnitId !== null, fn (Builder $query) => $query->where(fn (Builder $scope) => $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId))); }
    private function criteria(Builder $query, array $criteria): Builder { $search = trim((string) ($criteria['search'] ?? '')); if ($search !== '') { $query->where(fn (Builder $scope) => $scope->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")); } if (! empty($criteria['vehicle_make_id'])) { $query->where('vehicle_make_id', (int) $criteria['vehicle_make_id']); } if (array_key_exists('is_active', $criteria) && $criteria['is_active'] !== null && $criteria['is_active'] !== '') { $query->where('is_active', $criteria['is_active']); } return $query; }
    private function assertCodeUnique(int $tenantId, int $makeId, string $code, ?int $ignoreId = null): void { $query = VehicleModel::query()->withTrashed()->where('tenant_id', $tenantId)->where('vehicle_make_id', $makeId)->where('code', $code); if ($ignoreId !== null) { $query->whereKeyNot($ignoreId); } if ($query->exists()) { throw new InvalidArgumentException('Vehicle model code already exists for this make.'); } }
}
