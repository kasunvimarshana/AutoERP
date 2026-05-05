<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Finance\Application\DTOs\AccountData;
use Modules\Finance\Domain\Entities\Account;
use Modules\Finance\Domain\Exceptions\AccountNotFoundException;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class UpdateAccountService extends BaseService implements UpdateAccountServiceInterface
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepository)
    {
        parent::__construct($accountRepository);
    }

    protected function handle(array $data): Account
    {
        $id = (int) ($data['id'] ?? 0);
        $account = $this->accountRepository->find($id);

        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        $dto = AccountData::fromArray($data);


        if ($dto->rowVersion !== $account->getRowVersion()) {
            throw new ConcurrentModificationException('Account', $id);
        }

        $account->update(
            code: $dto->code,
            name: $dto->name,
            type: $dto->type,
            normalBalance: $dto->normal_balance,
            parentId: $dto->parent_id,
            subType: $dto->sub_type,
            isSystem: $dto->is_system,
            isBankAccount: $dto->is_bank_account,
            isCreditCard: $dto->is_credit_card,
            currencyId: $dto->currency_id,
            description: $dto->description,
            isActive: $dto->is_active,
        );

        $account->setHierarchy($dto->path, $dto->depth);

        return $this->accountRepository->save($account);
    }
}
