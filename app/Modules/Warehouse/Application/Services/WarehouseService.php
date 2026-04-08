<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Domain\Exceptions\WarehouseNotFoundException;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

final class WarehouseService implements WarehouseServiceInterface
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $repository,
    ) {}

    public function create(WarehouseData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $payload                = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['tenant_id']  = $tenantId;
            $payload['uuid']       = (string) Str::uuid();

            return $this->repository->create($payload);
        });
    }

    public function update(int $id, WarehouseData $dto): mixed
    {
        $record = $this->repository->find($id);
        if (! $record) {
            throw new WarehouseNotFoundException($id);
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
            throw new WarehouseNotFoundException($id);
        }

        return $this->repository->delete($id);
    }

    public function find(mixed $id): mixed
    {
        $record = $this->repository->find($id);
        if (! $record) {
            throw new WarehouseNotFoundException($id);
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
}
