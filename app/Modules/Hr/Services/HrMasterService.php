<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

abstract class HrMasterService
{
    /** @var class-string<Model> */
    protected string $modelClass;
    protected string $label;
    protected bool $hasSortOrder = false;

    public function create(object $data): Model
    {
        $this->validateData($data);
        $this->assertCodeUnique($data->tenantId, $data->code);

        return $this->modelClass::query()->create($this->attributes($data));
    }

    public function update(Model $model, object $data): Model
    {
        if ((int) $model->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException("{$this->label} belongs to a different tenant.");
        }
        $this->validateData($data);
        $this->assertCodeUnique($data->tenantId, $data->code, (int) $model->getKey());
        $model->fill($this->attributes($data))->save();

        return $model->refresh();
    }

    public function delete(Model $model): void
    {
        if (method_exists($model, 'employees') && $model->employees()->exists()) {
            throw new InvalidArgumentException("{$this->label} cannot be deleted while employees reference it.");
        }
        if (method_exists($model, 'assignments') && $model->assignments()->exists()) {
            throw new InvalidArgumentException("{$this->label} cannot be deleted while employees reference it.");
        }
        $model->delete();
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): Model
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        return $this->criteria($this->baseQuery($tenantId, $organizationUnitId), $criteria)
            ->orderBy($this->hasSortOrder ? 'sort_order' : 'name')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $criteria['is_active'] = true;
        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));
    }

    protected function validateData(object $data): void
    {
        if (trim($data->code) === '' || trim($data->name) === '') {
            throw new InvalidArgumentException("{$this->label} code and name are required.");
        }
        if ($data->organizationUnitId !== null) {
            $organization = OrganizationUnitModel::query()->findOrFail($data->organizationUnitId);
            if ((int) $organization->tenant_id !== $data->tenantId || ! (bool) $organization->is_active) {
                throw new InvalidArgumentException("{$this->label} organization unit must be active and belong to the tenant.");
            }
        }
    }

    protected function attributes(object $data): array
    {
        $attributes = [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'is_active' => $data->isActive,
        ];
        if ($this->hasSortOrder) {
            $attributes['sort_order'] = $data->sortOrder;
        }
        return $attributes;
    }

    protected function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return $this->modelClass::query()->forTenant($tenantId, $organizationUnitId);
    }

    private function criteria(Builder $query, array $criteria): Builder
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(fn (Builder $scope) => $scope->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }
        if (array_key_exists('is_active', $criteria) && $criteria['is_active'] !== null && $criteria['is_active'] !== '') {
            $query->where('is_active', $criteria['is_active']);
        }
        return $query;
    }

    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void
    {
        $query = $this->modelClass::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException("{$this->label} code already exists for this tenant.");
        }
    }
}
