<?php

declare(strict_types=1);

namespace Modules\Organization\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Repositories\Contracts\OrganizationRepositoryInterface;

class OrganizationRepository implements OrganizationRepositoryInterface
{
    public function __construct(
        protected Organization $model
    ) {}

    public function findById(int $id): ?Organization
    {
        return $this->model->find($id);
    }

    public function findByCode(string $code): ?Organization
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

    public function create(array $data): Organization
    {
        return $this->model->create($data);
    }

    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);
        return $organization->fresh();
    }

    public function delete(Organization $organization): bool
    {
        return $organization->delete();
    }

    public function getRoots(): Collection
    {
        return $this->model->whereNull('parent_id')->get();
    }

    public function getChildren(int $organizationId): Collection
    {
        return $this->model->where('parent_id', $organizationId)->get();
    }

    public function getDescendants(int $organizationId): Collection
    {
        $organization = $this->findById($organizationId);
        return $organization ? $organization->descendants() : new Collection();
    }

    public function getByType(string $type): Collection
    {
        return $this->model->where('organization_type', $type)->get();
    }

    public function getActive(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    public function search(string $query): Collection
    {
        return $this->model
            ->where('code', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->orWhere('legal_name', 'LIKE', "%{$query}%")
            ->get();
    }
}
