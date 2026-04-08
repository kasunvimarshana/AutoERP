<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Customer\Application\Contracts\CustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Events\CustomerCreated;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

final class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
    ) {}

    public function create(CustomerData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $payload              = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['tenant_id'] = $tenantId;
            $payload['uuid']      = (string) Str::uuid();

            $customer = $this->repository->create($payload);

            CustomerCreated::dispatch($customer, $tenantId);

            return $customer;
        });
    }

    public function update(int $id, CustomerData $dto): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new CustomerNotFoundException($id);
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
            throw new CustomerNotFoundException($id);
        }

        return $this->repository->delete($id);
    }

    public function find(mixed $id): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new CustomerNotFoundException($id);
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
