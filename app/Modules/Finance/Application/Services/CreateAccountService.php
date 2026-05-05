<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateAccountServiceInterface;
use Modules\Finance\Application\DTOs\AccountData;
use Modules\Finance\Domain\Entities\Account;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class CreateAccountService extends BaseService implements CreateAccountServiceInterface
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepository)
    {
        parent::__construct($accountRepository);
    }

    protected function handle(array $data): Account
    {
        $dto = AccountData::fromArray($data);

        $account = new Account(
            tenantId: $dto->tenantId,
            code: $dto->code,
            name: $dto->name,
            type: $dto->type,
            normalBalance: $dto->normal_balance,
            parentId: $dto->parent_id,
            subType: $dto->sub_type,
            isSystem: $dto->is_system,
            isBankAccount: $dto->is_bank_account,
            isCreditCard: $dto->is_credit_card,
            currencyId: $dto->currencyId,
            description: $dto->description,
            isActive: $dto->isActive,
            path: $dto->path,
            depth: $dto->depth,
        );

        return $this->accountRepository->save($account);
    }
}
