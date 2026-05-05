<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\BankTransaction;
use Modules\Finance\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;

class EloquentBankTransactionRepository extends EloquentRepository implements BankTransactionRepositoryInterface
{
    public function __construct(BankTransactionModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (BankTransactionModel $m): BankTransaction => $this->mapToDomain($m));
    }

    public function save(BankTransaction $bt): BankTransaction
    {
        $data = [
            'tenant_id' => $bt->getTenantId(),
            'bank_account_id' => $bt->getBankAccountId(),
            'external_id' => $bt->getExternalId(),
            'transaction_date' => $bt->getTransactionDate()->format('Y-m-d'),
            'description' => $bt->getDescription(),
            'amount' => $bt->getAmount(),
            'balance' => $bt->getBalance(),
            'type' => $bt->getType(),
            'status' => $bt->getStatus(),
            'matched_journal_entry_id' => $bt->getMatchedJournalEntryId(),
            'category_rule_id' => $bt->getCategoryRuleId(),
            'row_version' => $bt->getRowVersion(),
        ];

        $model = $bt->getId() ? $this->update($bt->getId(), $data) : $this->create($data);

        /** @var BankTransactionModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapToDomain(BankTransactionModel $m): BankTransaction
    {
        return new BankTransaction(
            tenantId: $m->tenant_id !== null ? (int) $m->tenant_id : null,
            bankAccountId: (int) $m->bank_account_id,
            description: (string) $m->description,
            amount: (float) $m->amount,
            type: (string) $m->type,
            transactionDate: $m->transaction_date,
            externalId: $m->external_id,
            balance: $m->balance !== null ? (float) $m->balance : null,
            status: (string) $m->status,
            matchedJournalEntryId: $m->matched_journal_entry_id !== null ? (int) $m->matched_journal_entry_id : null,
            categoryRuleId: $m->category_rule_id !== null ? (int) $m->category_rule_id : null,
            rowVersion: (int) ($m->row_version ?? 1),
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
