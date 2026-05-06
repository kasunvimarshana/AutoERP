<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateBankAccountServiceInterface;
use Modules\Finance\Application\DTOs\BankAccountData;
use Modules\Finance\Domain\Entities\BankAccount;
use Modules\Finance\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;

class CreateBankAccountService extends BaseService implements CreateBankAccountServiceInterface
{
    public function __construct(private readonly BankAccountRepositoryInterface $bankAccountRepository)
    {
        parent::__construct($bankAccountRepository);
    }

    protected function handle(array $data): BankAccount
    {
        $dto = BankAccountData::fromArray($data);

        $bankAccount = new BankAccount(
            tenantId: $dto->tenantId,
            accountId: $dto->accountId,
            name: $dto->name,
            bankName: $dto->bankName,
            accountNumber: $dto->accountNumber,
            currencyId: $dto->currencyId,
            routingNumber: $dto->routingNumber,
            currentBalance: $dto->currentBalance,
            feedProvider: $dto->feedProvider,
            isActive: $dto->isActive,
        );

        return $this->bankAccountRepository->save($bankAccount);
    }
}
