<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Domain\Entities\JournalEntryLine;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;

class EloquentJournalEntryRepository extends EloquentRepository implements JournalEntryRepositoryInterface
{
    public function __construct(JournalEntryModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (JournalEntryModel $model): JournalEntry => $this->mapModelToDomainEntity($model));
    }

    public function save(JournalEntry $journalEntry): JournalEntry
    {
        $data = [
            'tenant_id' => $journalEntry->getTenantId(),
            'fiscal_period_id' => $journalEntry->getFiscalPeriodId(),
            'entry_number' => $journalEntry->getEntryNumber(),
            'entry_type' => $journalEntry->getEntryType(),
            'reference_type' => $journalEntry->getReferenceType(),
            'reference_id' => $journalEntry->getReferenceId(),
            'description' => $journalEntry->getDescription(),
            'entry_date' => $journalEntry->getEntryDate()->format('Y-m-d'),
            'posting_date' => $journalEntry->getPostingDate()?->format('Y-m-d'),
            'status' => $journalEntry->getStatus(),
            'is_reversed' => $journalEntry->isReversed(),
            'reversal_entry_id' => $journalEntry->getReversalEntryId(),
            'created_by' => $journalEntry->getCreatedBy(),
            'posted_by' => $journalEntry->getPostedBy(),
            'posted_at' => $journalEntry->getPostedAt(),
        ];

        $model = DB::transaction(function () use ($journalEntry, $data): JournalEntryModel {
            if ($journalEntry->getId()) {
                /** @var JournalEntryModel $updated */
                $updated = $this->update($journalEntry->getId(), $data);

                $updated->lines()->delete();
                $this->persistLines($updated, $journalEntry->getLines());

                return $updated->fresh(['lines']);
            }

            /** @var JournalEntryModel $created */
            $created = $this->create($data);
            $this->persistLines($created, $journalEntry->getLines());

            return $created->fresh(['lines']);
        });

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?JournalEntry
    {
        /** @var JournalEntryModel|null $model */
        $model = $this->model->newQuery()
            ->with('lines')
            ->find($id, $columns);

        return $model ? $this->toDomainEntity($model) : null;
    }

    protected function applyCriteria(): void
    {
        parent::applyCriteria();

        $this->provider->with('lines');
    }

    /**
     * @param array<JournalEntryLine> $lines
     */
    private function persistLines(JournalEntryModel $model, array $lines): void
    {
        foreach ($lines as $line) {
            JournalEntryLineModel::query()->create([
                'journal_entry_id' => (int) $model->id,
                'account_id' => $line->getAccountId(),
                'description' => $line->getDescription(),
                'debit_amount' => $line->getDebitAmount(),
                'credit_amount' => $line->getCreditAmount(),
                'currency_id' => $line->getCurrencyId(),
                'exchange_rate' => $line->getExchangeRate(),
                'base_debit_amount' => $line->getBaseDebitAmount(),
                'base_credit_amount' => $line->getBaseCreditAmount(),
                'cost_center_id' => $line->getCostCenterId(),
                'metadata' => $line->getMetadata(),
            ]);
        }
    }

    private function mapModelToDomainEntity(JournalEntryModel $model): JournalEntry
    {
        $lines = [];
        foreach ($model->lines as $line) {
            $lines[] = new JournalEntryLine(
                accountId: (int) $line->account_id,
                debitAmount: (float) $line->debit_amount,
                creditAmount: (float) $line->credit_amount,
                description: $line->description,
                currencyId: $line->currency_id !== null ? (int) $line->currency_id : null,
                exchangeRate: (float) $line->exchange_rate,
                baseDebitAmount: (float) $line->base_debit_amount,
                baseCreditAmount: (float) $line->base_credit_amount,
                costCenterId: $line->cost_center_id !== null ? (int) $line->cost_center_id : null,
                metadata: is_array($line->metadata) ? $line->metadata : null,
                id: (int) $line->id,
            );
        }

        return new JournalEntry(
            tenantId: (int) $model->tenant_id,
            fiscalPeriodId: (int) $model->fiscal_period_id,
            entryDate: $model->entry_date,
            createdBy: (int) $model->created_by,
            lines: $lines,
            entryType: (string) $model->entry_type,
            entryNumber: $model->entry_number,
            referenceType: $model->reference_type,
            referenceId: $model->reference_id !== null ? (int) $model->reference_id : null,
            description: $model->description,
            postingDate: $model->posting_date,
            status: (string) $model->status,
            isReversed: (bool) $model->is_reversed,
            reversalEntryId: $model->reversal_entry_id !== null ? (int) $model->reversal_entry_id : null,
            postedBy: $model->posted_by !== null ? (int) $model->posted_by : null,
            postedAt: $model->posted_at,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
