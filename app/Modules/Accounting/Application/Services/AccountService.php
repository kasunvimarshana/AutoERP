<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Application\Contracts\AccountServiceInterface;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Exceptions\AccountNotFoundException;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class AccountService implements AccountServiceInterface
{
    public function __construct(private readonly AccountRepositoryInterface $repo) {}

    public function findById(int $id): Account
    {
        $account = $this->repo->findById($id);
        if (!$account) {
            throw new AccountNotFoundException($id);
        }
        return $account;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repo->findByTenant($tenantId, $perPage, $page);
    }

    public function create(array $data): Account
    {
        $account = $this->repo->create($data);
        event(new \Modules\Accounting\Domain\Events\AccountCreated($account->getTenantId(), $account->getId()));
        return $account;
    }

    public function update(int $id, array $data): Account
    {
        $account = $this->repo->update($id, $data);
        if (!$account) {
            throw new AccountNotFoundException($id);
        }
        return $account;
    }

    public function delete(int $id): bool
    {
        $account = $this->repo->findById($id);
        if (!$account) {
            throw new AccountNotFoundException($id);
        }
        return $this->repo->delete($id);
    }
}
