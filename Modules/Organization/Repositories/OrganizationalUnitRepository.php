<?php

declare(strict_types=1);

namespace Modules\Organization\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Entities\OrganizationalUnit;
use Modules\Organization\Repositories\Contracts\OrganizationalUnitRepositoryInterface;

class OrganizationalUnitRepository implements OrganizationalUnitRepositoryInterface
{
    public function __construct(
        protected OrganizationalUnit $model
    ) {}

    public function findById(int $id): ?OrganizationalUnit
    {
        return $this->model->find($id);
    }

    public function findByCode(string $code): ?OrganizationalUnit
    {
        return $this->model->where('code', $code)->first();
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function create(array $data): OrganizationalUnit
    {
        return $this->model->create($data);
    }

    public function update(OrganizationalUnit $unit, array $data): OrganizationalUnit
    {
        $unit->update($data);
        return $unit->fresh();
    }

    public function delete(OrganizationalUnit $unit): bool
    {
        return $unit->delete();
    }

    public function getRoots(): Collection
    {
        return $this->model->whereNull('parent_unit_id')->get();
    }

    public function getChildren(int $unitId): Collection
    {
        return $this->model->where('parent_unit_id', $unitId)->get();
    }

    public function getDescendants(int $unitId): Collection
    {
        $unit = $this->findById($unitId);
        return $unit ? $unit->descendants() : new Collection();
    }

    public function getByOrganization(int $organizationId): Collection
    {
        return $this->model->where('organization_id', $organizationId)->get();
    }

    public function getByLocation(int $locationId): Collection
    {
        return $this->model->where('location_id', $locationId)->get();
    }

    public function getByType(string $type): Collection
    {
        return $this->model->where('unit_type', $type)->get();
    }

    public function getActive(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    public function getByManager(int $managerId): Collection
    {
        return $this->model->where('manager_id', $managerId)->get();
    }

    public function search(string $query): Collection
    {
        return $this->model
            ->where('code', 'LIKE', "%{$query}%")
            ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$query}%"])
            ->orWhereRaw("JSON_EXTRACT(description, '$.en') LIKE ?", ["%{$query}%"])
            ->get();
    }
}
