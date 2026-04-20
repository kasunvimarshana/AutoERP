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
            tenantId: $dto->tenant_id,
            accountId: $dto->account_id,
            name: $dto->name,
            bankName: $dto->bank_name,
            accountNumber: $dto->account_number,
            currencyId: $dto->currency_id,
            routingNumber: $dto->routing_number,
            currentBalance: $dto->current_balance,
            feedProvider: $dto->feed_provider,
            isActive: $dto->is_active,
        );

        return $this->bankAccountRepository->save($bankAccount);
    }
}
