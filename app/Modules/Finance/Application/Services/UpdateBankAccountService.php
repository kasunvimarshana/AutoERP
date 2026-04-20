<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdateBankAccountServiceInterface;
use Modules\Finance\Application\DTOs\BankAccountData;
use Modules\Finance\Domain\Entities\BankAccount;
use Modules\Finance\Domain\Exceptions\BankAccountNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;

class UpdateBankAccountService extends BaseService implements UpdateBankAccountServiceInterface
{
    public function __construct(private readonly BankAccountRepositoryInterface $bankAccountRepository)
    {
        parent::__construct($bankAccountRepository);
    }

    protected function handle(array $data): BankAccount
    {
        $dto = BankAccountData::fromArray($data);
        /** @var BankAccount|null $ba */
        $ba = $this->bankAccountRepository->find((int) $dto->id);
        if (! $ba) {
            throw new BankAccountNotFoundException((int) $dto->id);
        }
        $ba->update(
            name: $dto->name,
            bankName: $dto->bank_name,
            accountNumber: $dto->account_number,
            routingNumber: $dto->routing_number,
            isActive: $dto->is_active,
        );

        return $this->bankAccountRepository->save($ba);
    }
}
