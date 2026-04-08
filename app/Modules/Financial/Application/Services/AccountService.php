<?php

declare(strict_types=1);

namespace Modules\Financial\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Financial\Application\Contracts\AccountServiceInterface;
use Modules\Financial\Domain\Contracts\Repositories\AccountRepositoryInterface;
use Modules\Financial\Domain\Events\AccountCreated;
use Modules\Financial\Domain\Exceptions\AccountNotFoundException;

class AccountService extends BaseService implements AccountServiceInterface
{
    public function __construct(AccountRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — delegates to createAccount.
     */
    protected function handle(array $data): mixed
    {
        return $this->createAccount($data);
    }

    /**
     * Create a new chart-of-accounts entry and dispatch an event.
     */
    public function createAccount(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $account = $this->repository->create($data);
            $this->addEvent(new AccountCreated((int) ($account->tenant_id ?? 0), $account->id));
            $this->dispatchEvents();

            return $account;
        });
    }

    /**
     * Update an existing account.
     */
    public function updateAccount(string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $account = $this->repository->find($id);
            if (! $account) {
                throw new AccountNotFoundException($id);
            }

            return $this->repository->update($id, $data);
        });
    }
}
