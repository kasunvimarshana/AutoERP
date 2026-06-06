<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

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
        if (SupplierCategory::query()->withTrashed()->where('tenant_id', $data->tenantId)->where('code', $data->code)->exists()) {
            throw new InvalidArgumentException('Supplier category code already exists for this tenant.');
        }

        if ($data->parentId !== null) {
            $parent = SupplierCategory::query()->findOrFail($data->parentId);
            if ((int) $parent->tenant_id !== $data->tenantId) {
                throw new InvalidArgumentException('Supplier category parent belongs to a different tenant.');
            }
            if ($data->organizationUnitId !== null && $parent->organization_unit_id !== null && (int) $parent->organization_unit_id !== $data->organizationUnitId) {
                throw new InvalidArgumentException('Supplier category parent belongs to a different organization unit.');
            }
        }

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
}
