<?php

declare(strict_types=1);

namespace Modules\Organization\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Entities\Location;
use Modules\Organization\Repositories\Contracts\LocationRepositoryInterface;

class LocationRepository implements LocationRepositoryInterface
{
    public function __construct(
        protected Location $model
    ) {}

    public function findById(int $id): ?Location
    {
        return $this->model->find($id);
    }

    public function findByCode(string $code): ?Location
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

    public function create(array $data): Location
    {
        return $this->model->create($data);
    }

    public function update(Location $location, array $data): Location
    {
        $location->update($data);
        return $location->fresh();
    }

    public function delete(Location $location): bool
    {
        return $location->delete();
    }

    public function getByOrganization(int $organizationId): Collection
    {
        return $this->model->where('organization_id', $organizationId)->get();
    }

    public function getByType(string $type): Collection
    {
        return $this->model->where('location_type', $type)->get();
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
            ->get();
    }
}
