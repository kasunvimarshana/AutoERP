<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalEntryLine;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Accounting\Infrastructure\Models\JournalEntryLineModel;
use Modules\Accounting\Infrastructure\Models\JournalEntryModel;

class JournalEntryRepository extends BaseRepository implements JournalEntryRepositoryInterface
{
    protected function model(): string
    {
        return JournalEntryModel::class;
    }

    public function nextEntryNumber(int $tenantId): string
    {
        $count = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->withTrashed()
            ->count();

        $sequence = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);

        return "JE-{$tenantId}-{$sequence}";
    }

    public function findById(int $id, int $tenantId): ?JournalEntry
    {
        $model = $this->newQuery()
            ->with('lines')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->with('lines')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (JournalEntryModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(JournalEntry $entry): JournalEntry
    {
        if ($entry->id !== null) {
            $model = $this->newQuery()
                ->where('id', $entry->id)
                ->where('tenant_id', $entry->tenantId)
                ->firstOrFail();
        } else {
            $model = new JournalEntryModel;
            $model->tenant_id = $entry->tenantId;
        }

        $model->entry_number = $entry->entryNumber;
        $model->entry_date = $entry->entryDate;
        $model->reference = $entry->reference;
        $model->description = $entry->description;
        $model->currency = $entry->currency;
        $model->status = $entry->status->value;
        $model->total_debit = $entry->totalDebit;
        $model->total_credit = $entry->totalCredit;
        $model->save();

        $model->lines()->delete();

        foreach ($entry->lines as $line) {
            $lineModel = new JournalEntryLineModel;
            $lineModel->journal_entry_id = $model->id;
            $lineModel->account_id = $line->accountId;
            $lineModel->description = $line->description;
            $lineModel->debit_amount = $line->debitAmount;
            $lineModel->credit_amount = $line->creditAmount;
            $lineModel->created_at = now();
            $lineModel->save();
        }

        $model->load('lines');

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Journal entry with ID {$id} not found.");
        }

        $model->delete();
    }

    private function toDomain(JournalEntryModel $model): JournalEntry
    {
        $lines = $model->lines->map(function (JournalEntryLineModel $line): JournalEntryLine {
            return new JournalEntryLine(
                id: $line->id,
                journalEntryId: $line->journal_entry_id,
                accountId: $line->account_id,
                accountCode: null,
                accountName: null,
                description: $line->description,
                debitAmount: bcadd((string) $line->debit_amount, '0', 4),
                creditAmount: bcadd((string) $line->credit_amount, '0', 4),
                createdAt: $line->created_at?->toIso8601String(),
            );
        })->all();

        return new JournalEntry(
            id: $model->id,
            tenantId: $model->tenant_id,
            entryNumber: $model->entry_number,
            entryDate: $model->entry_date instanceof \Carbon\Carbon
                             ? $model->entry_date->toDateString()
                             : (string) $model->entry_date,
            reference: $model->reference,
            description: $model->description,
            currency: $model->currency,
            status: JournalEntryStatus::from($model->status),
            totalDebit: bcadd((string) $model->total_debit, '0', 4),
            totalCredit: bcadd((string) $model->total_credit, '0', 4),
            lines: $lines,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
