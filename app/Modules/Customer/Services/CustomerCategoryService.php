<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Customer\DTOs\CustomerCategoryData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCategory;
use Modules\Customer\Models\CustomerCategoryAssignment;
use Modules\Customer\Validators\CustomerValidationService;

final class CustomerCategoryService
{
    public function __construct(private readonly CustomerValidationService $validator) {}

    public function create(CustomerCategoryData $data): CustomerCategory
    {
        if (trim($data->code) === '' || trim($data->name) === '') {
            throw new InvalidArgumentException('Customer category code and name are required.');
        }
        $this->assertCodeUnique($data->tenantId, $data->code);
        $this->validator->assertOrganizationUsable($data->tenantId, $data->organizationUnitId);
        $this->assertParent($data);

        return CustomerCategory::query()->create([
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

    public function update(CustomerCategory $category, CustomerCategoryData $data): CustomerCategory
    {
        if ((int) $category->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Customer category belongs to a different tenant.');
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

    public function delete(CustomerCategory $category): void
    {
        if ($category->customers()->exists()) {
            throw new InvalidArgumentException('Customer category cannot be deleted while customers reference it.');
        }
        $category->delete();
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): CustomerCategory
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
    public function assign(Customer $customer, array $categoryIds): void
    {
        $categoryIds = array_values(array_unique($categoryIds));
        foreach ($categoryIds as $categoryId) {
            $this->validator->assertCategoryUsable($customer, $categoryId);
        }

        $customer->categoryAssignments()->delete();

        foreach ($categoryIds as $categoryId) {
            CustomerCategoryAssignment::query()->create([
                'tenant_id' => $customer->tenant_id,
                'organization_unit_id' => $customer->organization_unit_id,
                'customer_id' => $customer->getKey(),
                'customer_category_id' => $categoryId,
            ]);
        }
    }

    public function attach(Customer $customer, int $categoryId): CustomerCategory
    {
        $category = $this->validator->assertCategoryUsable($customer, $categoryId);
        if ($customer->categoryAssignments()->where('customer_category_id', $categoryId)->exists()) {
            throw new InvalidArgumentException('Customer category is already assigned.');
        }

        CustomerCategoryAssignment::query()->create([
            'tenant_id' => $customer->tenant_id,
            'organization_unit_id' => $customer->organization_unit_id,
            'customer_id' => $customer->getKey(),
            'customer_category_id' => $categoryId,
        ]);

        return $category->load('parent');
    }

    public function detach(Customer $customer, int $categoryId): void
    {
        $deleted = $customer->categoryAssignments()
            ->where('customer_category_id', $categoryId)
            ->delete();
        if ($deleted === 0) {
            throw new InvalidArgumentException('Customer category assignment was not found.');
        }
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = CustomerCategory::query()->where('tenant_id', $tenantId);
        if ($organizationUnitId !== null) {
            $query->where(fn (Builder $scope): Builder => $scope
                ->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $organizationUnitId));
        }

        return $query;
    }

    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void
    {
        $query = CustomerCategory::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('Customer category code already exists for this tenant.');
        }
    }

    private function assertParent(CustomerCategoryData $data, ?int $categoryId = null): void
    {
        if ($data->parentId === null) {
            return;
        }
        if ($categoryId !== null && $data->parentId === $categoryId) {
            throw new InvalidArgumentException('Customer category cannot be its own parent.');
        }
        $parent = CustomerCategory::query()->findOrFail($data->parentId);
        if ((int) $parent->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Customer category parent belongs to a different tenant.');
        }
        if ($data->organizationUnitId !== null && $parent->organization_unit_id !== null
            && (int) $parent->organization_unit_id !== $data->organizationUnitId) {
            throw new InvalidArgumentException('Customer category parent belongs to a different organization unit.');
        }
    }
}
