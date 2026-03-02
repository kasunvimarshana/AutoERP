<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Commands\CreateAccountCommand;
use Modules\Accounting\Application\Commands\DeleteAccountCommand;
use Modules\Accounting\Application\Commands\UpdateAccountCommand;
use Modules\Accounting\Application\Handlers\CreateAccountHandler;
use Modules\Accounting\Application\Handlers\DeleteAccountHandler;
use Modules\Accounting\Application\Handlers\UpdateAccountHandler;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Entities\Account;

/**
 * Service orchestrating all chart-of-accounts operations.
 *
 * Controllers must interact with the account domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class AccountService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly CreateAccountHandler $createAccountHandler,
        private readonly UpdateAccountHandler $updateAccountHandler,
        private readonly DeleteAccountHandler $deleteAccountHandler,
    ) {}

    /**
     * Retrieve a paginated list of accounts for the given tenant.
     *
     * @return array{items: Account[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listAccounts(int $tenantId, int $page, int $perPage): array
    {
        return $this->accountRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single account by its identifier within the given tenant.
     */
    public function findAccountById(int $accountId, int $tenantId): ?Account
    {
        return $this->accountRepository->findById($accountId, $tenantId);
    }

    /**
     * Create a new account and return the persisted entity.
     */
    public function createAccount(CreateAccountCommand $command): Account
    {
        return $this->createAccountHandler->handle($command);
    }

    /**
     * Update an existing account and return the updated entity.
     */
    public function updateAccount(UpdateAccountCommand $command): Account
    {
        return $this->updateAccountHandler->handle($command);
    }

    /**
     * Delete an account.
     */
    public function deleteAccount(DeleteAccountCommand $command): void
    {
        $this->deleteAccountHandler->handle($command);
    }
}
