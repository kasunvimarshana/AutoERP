<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Supplier\DTOs\SupplierCategoryData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierCategory;
use Modules\Supplier\Models\SupplierCategoryAssignment;
use Modules\Supplier\Validators\SupplierValidationService;

final class SupplierCategoryService
{
    public function __construct(private readonly SupplierValidationService $validator) {}

    public function create(SupplierCategoryData $data): SupplierCategory
    {
        if (trim($data->code) === '' || trim($data->name) === '') {
            throw new InvalidArgumentException('Supplier category code and name are required.');
        }
        $this->assertCodeUnique($data->tenantId, $data->code);
        $this->validator->assertOrganizationUsable($data->tenantId, $data->organizationUnitId);
        $this->assertParent($data);

        return SupplierCategory::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'parent_id' => $data->parentId,
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'is_active' => $data->isActive,
            'sort_order' => $data->sortOrder,
        ]);
    }

    public function update(SupplierCategory $category, SupplierCategoryData $data): SupplierCategory
    {
        if ((int) $category->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Supplier category belongs to a different tenant.');
        }
        $this->assertCodeUnique($data->tenantId, $data->code, (int) $category->getKey());
        $this->validator->assertOrganizationUsable($data->tenantId, $data->organizationUnitId);
        $this->assertParent($data, (int) $category->getKey());

        $category->fill([
            'organization_unit_id' => $data->organizationUnitId,
            'parent_id' => $data->parentId,
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'is_active' => $data->isActive,
            'sort_order' => $data->sortOrder,
        ])->save();

        return $category->refresh()->load('parent');
    }

    public function delete(SupplierCategory $category): void
    {
        if ($category->suppliers()->exists()) {
            throw new InvalidArgumentException('Supplier category cannot be deleted while suppliers reference it.');
        }
        $category->delete();
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): SupplierCategory
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->with('parent')->findOrFail($id);
    }

    public function paginate(
        array $criteria,
        int $tenantId,
        ?int $organizationUnitId,
        int $perPage,
    ): LengthAwarePaginator {
        $query = $this->baseQuery($tenantId, $organizationUnitId)->with('parent');
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }
        if (array_key_exists('is_active', $criteria) && $criteria['is_active'] !== null && $criteria['is_active'] !== '') {
            $query->where('is_active', $criteria['is_active']);
        }

        return $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);
    }

    public function lookup(
        array $criteria,
        int $tenantId,
        ?int $organizationUnitId,
        int $perPage,
    ): LengthAwarePaginator {
        $criteria['is_active'] = true;

        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public function assign(Supplier $supplier, array $categoryIds): void
    {
        $categoryIds = array_values(array_unique($categoryIds));
        foreach ($categoryIds as $categoryId) {
            $this->validator->assertCategoryUsable($supplier, $categoryId);
        }

        $supplier->categoryAssignments()->delete();

        foreach ($categoryIds as $categoryId) {
            SupplierCategoryAssignment::query()->create([
                'tenant_id' => $supplier->tenant_id,
                'organization_unit_id' => $supplier->organization_unit_id,
                'supplier_id' => $supplier->getKey(),
                'supplier_category_id' => $categoryId,
            ]);
        }
    }

    public function attach(Supplier $supplier, int $categoryId): SupplierCategory
    {
        $category = $this->validator->assertCategoryUsable($supplier, $categoryId);
        if ($supplier->categoryAssignments()->where('supplier_category_id', $categoryId)->exists()) {
            throw new InvalidArgumentException('Supplier category is already assigned.');
        }

        SupplierCategoryAssignment::query()->create([
            'tenant_id' => $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
            'supplier_id' => $supplier->getKey(),
            'supplier_category_id' => $categoryId,
        ]);

        return $category->load('parent');
    }

    public function detach(Supplier $supplier, int $categoryId): void
    {
        $deleted = $supplier->categoryAssignments()
            ->where('supplier_category_id', $categoryId)
            ->delete();
        if ($deleted === 0) {
            throw new InvalidArgumentException('Supplier category assignment was not found.');
        }
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = SupplierCategory::query()->where('tenant_id', $tenantId);
        if ($organizationUnitId !== null) {
            $query->where(fn (Builder $scope): Builder => $scope
                ->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $organizationUnitId));
        }

        return $query;
    }

    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void
    {
        $query = SupplierCategory::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('Supplier category code already exists for this tenant.');
        }
    }

    private function assertParent(SupplierCategoryData $data, ?int $categoryId = null): void
    {
        if ($data->parentId === null) {
            return;
        }
        if ($categoryId !== null && $data->parentId === $categoryId) {
            throw new InvalidArgumentException('Supplier category cannot be its own parent.');
        }
        $parent = SupplierCategory::query()->findOrFail($data->parentId);
        if ((int) $parent->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Supplier category parent belongs to a different tenant.');
        }
        if ($data->organizationUnitId !== null && $parent->organization_unit_id !== null
            && (int) $parent->organization_unit_id !== $data->organizationUnitId) {
            throw new InvalidArgumentException('Supplier category parent belongs to a different organization unit.');
        }
    }
}
