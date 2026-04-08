<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Warehouse\Application\Contracts\LocationServiceInterface;
use Modules\Warehouse\Application\DTOs\LocationData;
use Modules\Warehouse\Domain\Exceptions\LocationNotFoundException;
use Modules\Warehouse\Domain\RepositoryInterfaces\LocationRepositoryInterface;

final class LocationService implements LocationServiceInterface
{
    public function __construct(
        private readonly LocationRepositoryInterface $repository,
    ) {}

    public function create(LocationData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $payload               = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['tenant_id'] = $tenantId;
            $payload['uuid']      = (string) Str::uuid();

            $parent = $payload['parent_id'] ?? null;
            if ($parent !== null) {
                $parentRecord     = $this->repository->find($parent);
                $payload['level'] = $parentRecord ? ($parentRecord->level + 1) : 0;
                if ($parentRecord) {
                    $payload['path'] = $parentRecord->path !== null
                        ? $parentRecord->path . '/' . $parentRecord->id
                        : (string) $parentRecord->id;
                } else {
                    $payload['path'] = (string) $parent;
                }
            } else {
                $payload['level'] = 0;
                $payload['path']  = null;
            }

            return $this->repository->create($payload);
        });
    }

    public function update(int $id, LocationData $dto): mixed
    {
        $record = $this->repository->find($id);
        if (! $record) {
            throw new LocationNotFoundException($id);
        }

        return DB::transaction(function () use ($id, $dto) {
            $payload = array_filter($dto->toArray(), static fn ($v) => $v !== null);

            return $this->repository->update($id, $payload);
        });
    }

    public function delete(int $id): bool
    {
        $record = $this->repository->find($id);
        if (! $record) {
            throw new LocationNotFoundException($id);
        }

        return $this->repository->delete($id);
    }

    public function find(mixed $id): mixed
    {
        $record = $this->repository->find($id);
        if (! $record) {
            throw new LocationNotFoundException($id);
        }

        return $record;
    }

    public function list(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? config('core.pagination.per_page', 15);
        $repo    = clone $this->repository;

        foreach ($filters as $column => $value) {
            $repo->where($column, $value);
        }

        return $repo->paginate($perPage);
    }

    public function getTree(int $warehouseId): Collection
    {
        return $this->repository->getLocationTree($warehouseId);
    }
}
