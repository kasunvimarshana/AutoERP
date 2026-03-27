<?php

declare(strict_types=1);

namespace Modules\Account\Application\Services;

use Modules\Account\Application\Contracts\CreateAccountServiceInterface;
use Modules\Account\Application\DTOs\AccountData;
use Modules\Account\Domain\Entities\Account;
use Modules\Account\Domain\Events\AccountCreated;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

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
            tenantId: $dto->tenant_id,
            code: $dto->code,
            name: $dto->name,
            type: $dto->type,
            subtype: $dto->subtype,
            description: $dto->description,
            currency: $dto->currency ?? 'USD',
            balance: $dto->balance ?? 0.0,
            isSystem: $dto->is_system ?? false,
            parentId: $dto->parent_id,
            status: $dto->status ?? 'active',
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        $saved = $this->accountRepository->save($account);

        $this->addEvent(new AccountCreated($saved));

        return $saved;
    }
}
