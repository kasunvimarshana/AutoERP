<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\BatchLotServiceInterface;
use Modules\Inventory\Application\DTOs\BatchLotData;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchLotRepositoryInterface;

final class BatchLotService implements BatchLotServiceInterface
{
    public function __construct(
        private readonly BatchLotRepositoryInterface $repository,
    ) {}

    public function create(BatchLotData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $payload                = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['tenant_id']   = $tenantId;
            $payload['uuid']        = (string) Str::uuid();

            return $this->repository->create($payload);
        });
    }

    public function update(int $id, BatchLotData $dto): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new NotFoundException('BatchLot', $id);
        }

        return DB::transaction(function () use ($id, $dto) {
            $payload = array_filter($dto->toArray(), static fn ($v) => $v !== null);

            return $this->repository->update($id, $payload);
        });
    }

    public function find(mixed $id): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new NotFoundException('BatchLot', $id);
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

    public function delete(int $id): void
    {
        $this->find($id);
        $this->repository->delete($id);
    }
}
