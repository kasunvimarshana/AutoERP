<?php

declare(strict_types=1);

namespace Modules\Account\Application\UseCases;

use Modules\Account\Application\DTOs\AccountData;
use Modules\Account\Domain\Entities\Account;
use Modules\Account\Domain\Events\AccountUpdated;
use Modules\Account\Domain\Exceptions\AccountNotFoundException;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class UpdateAccount
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepo) {}

    public function execute(int $id, AccountData $data): Account
    {
        $account = $this->accountRepo->find($id);
        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        $account->updateDetails(
            code: $data->code,
            name: $data->name,
            type: $data->type,
            subtype: $data->subtype,
            description: $data->description,
            currency: $data->currency ?? $account->getCurrency(),
            parentId: $data->parent_id,
            attributes: $data->attributes,
            metadata: $data->metadata,
        );

        if (isset($data->status)) {
            if ($data->status === 'active') {
                $account->activate();
            } elseif ($data->status === 'inactive') {
                $account->deactivate();
            }
        }

        $saved = $this->accountRepo->save($account);

        event(new AccountUpdated($saved));

        return $saved;
    }
}
