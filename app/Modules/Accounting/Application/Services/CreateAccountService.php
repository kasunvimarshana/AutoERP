<?php
namespace Modules\Accounting\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Accounting\Application\Contracts\CreateAccountServiceInterface;
use Modules\Accounting\Application\DTOs\AccountData;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Events\AccountCreated;
use Modules\Accounting\Domain\Repositories\AccountRepositoryInterface;

class CreateAccountService implements CreateAccountServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(AccountData $data): Account
    {
        $account = $this->accountRepository->create([
            'tenant_id'   => $data->tenantId,
            'code'        => $data->code,
            'name'        => $data->name,
            'type'        => $data->type,
            'parent_id'   => $data->parentId,
            'currency'    => $data->currency,
            'is_active'   => $data->isActive,
            'description' => $data->description,
        ]);

        $this->dispatcher->dispatch(new AccountCreated($account->tenantId, $account->id));

        return $account;
    }
}
