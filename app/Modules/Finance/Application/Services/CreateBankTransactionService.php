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
            tenantId: $dto->tenant_id,
            bankAccountId: $dto->bank_account_id,
            description: $dto->description,
            amount: $dto->amount,
            type: $dto->type,
            transactionDate: new \DateTimeImmutable($dto->transaction_date),
            externalId: $dto->external_id,
            balance: $dto->balance,
            status: $dto->status,
            matchedJournalEntryId: $dto->matched_journal_entry_id,
            categoryRuleId: $dto->category_rule_id,
        );

        return $this->bankTransactionRepository->save($bt);
    }
}
