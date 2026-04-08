<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Supplier\Application\Contracts\SupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Events\SupplierCreated;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

final class SupplierService implements SupplierServiceInterface
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
    ) {}

    public function create(SupplierData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $payload              = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['tenant_id'] = $tenantId;
            $payload['uuid']      = (string) Str::uuid();

            $supplier = $this->repository->create($payload);

            SupplierCreated::dispatch($supplier, $tenantId);

            return $supplier;
        });
    }

    public function update(int $id, SupplierData $dto): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new SupplierNotFoundException($id);
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
            throw new SupplierNotFoundException($id);
        }

        return $this->repository->delete($id);
    }

    public function find(mixed $id): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new SupplierNotFoundException($id);
        }

        return $record;
    }

    public function list(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? (int) config('core.pagination.per_page', 15);
        $repo    = clone $this->repository;

        foreach ($filters as $column => $value) {
            $repo->where($column, $value);
        }

        return $repo->paginate($perPage);
    }
}
