<?php

declare(strict_types=1);

namespace Modules\Account\Application\UseCases;

use Modules\Account\Application\DTOs\AccountData;
use Modules\Account\Domain\Entities\Account;
use Modules\Account\Domain\Events\AccountCreated;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class CreateAccount
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepo) {}

    public function execute(AccountData $data): Account
    {
        $account = new Account(
            tenantId: $data->tenant_id,
            code: $data->code,
            name: $data->name,
            type: $data->type,
            subtype: $data->subtype,
            description: $data->description,
            currency: $data->currency ?? 'USD',
            balance: $data->balance ?? 0.0,
            isSystem: $data->is_system ?? false,
            parentId: $data->parent_id,
            status: $data->status ?? 'active',
            attributes: $data->attributes,
            metadata: $data->metadata,
        );

        $saved = $this->accountRepo->save($account);

        event(new AccountCreated($saved));

        return $saved;
    }
}
