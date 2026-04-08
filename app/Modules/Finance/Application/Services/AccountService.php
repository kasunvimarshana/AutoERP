<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Application\Contracts\AccountServiceInterface;
use Modules\Finance\Application\DTOs\AccountData;
use Modules\Finance\Domain\Exceptions\AccountNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Finance\Domain\ValueObjects\AccountNature;
use Modules\Finance\Domain\ValueObjects\AccountType;

final class AccountService implements AccountServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function create(AccountData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $nature = $dto->nature
                ?? (new AccountType($dto->type))->defaultNature()->getValue();

            $payload = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['tenant_id']       = $tenantId;
            $payload['nature']          = $nature;
            $payload['current_balance'] = $dto->opening_balance;
            $payload['uuid']            = (string) Str::uuid();

            return $this->repository->create($payload);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, AccountData $dto): mixed
    {
        $account = $this->repository->find($id);
        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($id, $dto) {
            $payload = array_filter($dto->toArray(), static fn ($v) => $v !== null);

            return $this->repository->update($id, $payload);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $account = $this->repository->find($id);
        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        if ($account->is_system) {
            throw new \Modules\Core\Domain\Exceptions\DomainException(
                'System accounts cannot be deleted.',
                422
            );
        }

        return $this->repository->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function find(mixed $id): mixed
    {
        $account = $this->repository->find($id);
        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        return $account;
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code, int $tenantId): mixed
    {
        $account = $this->repository->findByCode($code, $tenantId);
        if (! $account) {
            throw new AccountNotFoundException($code);
        }

        return $account;
    }

    /**
     * {@inheritdoc}
     */
    public function findByType(string $type, int $tenantId): Collection
    {
        return $this->repository->findByType($type, $tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function getChartOfAccounts(int $tenantId): Collection
    {
        return $this->repository->getChartOfAccounts($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function list(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? config('core.pagination.per_page', 15);

        $repo = clone $this->repository;

        foreach ($filters as $column => $value) {
            $repo->where($column, $value);
        }

        return $repo->paginate($perPage);
    }
}
