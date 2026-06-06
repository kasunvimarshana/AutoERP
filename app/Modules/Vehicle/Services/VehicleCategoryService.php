<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleCategoryData;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\Vehicle\Validators\VehicleValidationService;

final class VehicleCategoryService
{
    public function __construct(private readonly VehicleValidationService $validator) {}
    public function create(VehicleCategoryData $data): VehicleCategory { $this->validate($data); $this->assertCodeUnique($data->tenantId, $data->code); $this->assertParent($data); return VehicleCategory::query()->create(['tenant_id' => $data->tenantId, 'organization_unit_id' => $data->organizationUnitId, 'parent_id' => $data->parentId, 'code' => $data->code, 'name' => $data->name, 'description' => $data->description, 'is_active' => $data->isActive, 'sort_order' => $data->sortOrder]); }
    public function update(VehicleCategory $category, VehicleCategoryData $data): VehicleCategory { if ((int) $category->tenant_id !== $data->tenantId) { throw new InvalidArgumentException('Vehicle category belongs to a different tenant.'); } $this->validate($data); $this->assertCodeUnique($data->tenantId, $data->code, (int) $category->getKey()); $this->assertParent($data, (int) $category->getKey()); $category->fill(['organization_unit_id' => $data->organizationUnitId, 'parent_id' => $data->parentId, 'code' => $data->code, 'name' => $data->name, 'description' => $data->description, 'is_active' => $data->isActive, 'sort_order' => $data->sortOrder])->save(); return $category->refresh()->load('parent'); }
    public function delete(VehicleCategory $category): void { if ($category->vehicles()->exists()) { throw new InvalidArgumentException('Vehicle category cannot be deleted while vehicles reference it.'); } $category->delete(); }
    public function find(int $id, int $tenantId, ?int $organizationUnitId): VehicleCategory { return $this->baseQuery($tenantId, $organizationUnitId)->with('parent')->findOrFail($id); }
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator { return $this->criteria($this->baseQuery($tenantId, $organizationUnitId)->with('parent'), $criteria)->orderBy('sort_order')->orderBy('name')->paginate($perPage); }
    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator { $criteria['is_active'] = true; return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50)); }
    private function validate(VehicleCategoryData $data): void { if (trim($data->code) === '' || trim($data->name) === '') { throw new InvalidArgumentException('Vehicle category code and name are required.'); } if ($data->sortOrder < 0) { throw new InvalidArgumentException('Vehicle category sort order cannot be negative.'); } $this->validator->assertOrganizationUsable($data->tenantId, $data->organizationUnitId); }
    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder { return VehicleCategory::query()->where('tenant_id', $tenantId)->when($organizationUnitId !== null, fn (Builder $query) => $query->where(fn (Builder $scope) => $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId))); }
    private function criteria(Builder $query, array $criteria): Builder { $search = trim((string) ($criteria['search'] ?? '')); if ($search !== '') { $query->where(fn (Builder $scope) => $scope->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")); } if (array_key_exists('is_active', $criteria) && $criteria['is_active'] !== null && $criteria['is_active'] !== '') { $query->where('is_active', $criteria['is_active']); } return $query; }
    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void { $query = VehicleCategory::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code); if ($ignoreId !== null) { $query->whereKeyNot($ignoreId); } if ($query->exists()) { throw new InvalidArgumentException('Vehicle category code already exists for this tenant.'); } }
    private function assertParent(VehicleCategoryData $data, ?int $categoryId = null): void { if ($data->parentId === null) { return; } if ($categoryId !== null && $data->parentId === $categoryId) { throw new InvalidArgumentException('Vehicle category cannot be its own parent.'); } $parent = VehicleCategory::query()->findOrFail($data->parentId); $this->validator->assertScope($data->tenantId, $data->organizationUnitId, (int) $parent->tenant_id, $parent->organization_unit_id); }
}
