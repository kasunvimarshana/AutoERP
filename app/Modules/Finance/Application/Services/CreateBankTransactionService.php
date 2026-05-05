<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateBankTransactionServiceInterface;
use Modules\Finance\Application\DTOs\BankTransactionData;
use Modules\Finance\Domain\Entities\BankTransaction;
use Modules\Finance\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;

class CreateBankTransactionService extends BaseService implements CreateBankTransactionServiceInterface
{
    public function __construct(private readonly BankTransactionRepositoryInterface $bankTransactionRepository)
    {
        parent::__construct($bankTransactionRepository);
    }

    protected function handle(array $data): BankTransaction
    {
        $dto = BankTransactionData::fromArray($data);

        $bt = new BankTransaction(
            tenantId: $dto->tenantId,
            bankAccountId: $dto->bankAccountId,
            description: $dto->description,
            amount: $dto->amount,
            type: $dto->type,
            transactionDate: new \DateTimeImmutable($dto->transactionDate),
            externalId: $dto->externalId,
            balance: $dto->balance,
            status: $dto->status,
            matchedJournalEntryId: $dto->matchedJournalEntryId,
            categoryRuleId: $dto->categoryRuleId,
        );

        return $this->bankTransactionRepository->save($bt);
    }
}
