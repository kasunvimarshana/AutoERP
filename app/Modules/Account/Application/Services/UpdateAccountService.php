<?php

declare(strict_types=1);

namespace Modules\Account\Application\Services;

use Modules\Account\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Account\Application\DTOs\AccountData;
use Modules\Account\Domain\Entities\Account;
use Modules\Account\Domain\Events\AccountUpdated;
use Modules\Account\Domain\Exceptions\AccountNotFoundException;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class UpdateAccountService extends BaseService implements UpdateAccountServiceInterface
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepository)
    {
        parent::__construct($accountRepository);
    }

    protected function handle(array $data): Account
    {
        $id = $data['id'];
        $account = $this->accountRepository->find($id);

        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        $dto = AccountData::fromArray($data);

        $account->updateDetails(
            code: $dto->code,
            name: $dto->name,
            type: $dto->type,
            subtype: $dto->subtype,
            description: $dto->description,
            currency: $dto->currency ?? $account->getCurrency(),
            parentId: $dto->parent_id,
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        if (isset($dto->status)) {
            if ($dto->status === 'active') {
                $account->activate();
            } elseif ($dto->status === 'inactive') {
                $account->deactivate();
            }
        }

        $saved = $this->accountRepository->save($account);

        $this->addEvent(new AccountUpdated($saved));

        return $saved;
    }
}
