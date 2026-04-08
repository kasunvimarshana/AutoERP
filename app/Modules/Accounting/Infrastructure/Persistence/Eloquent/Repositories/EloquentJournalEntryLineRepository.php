<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Modules\Accounting\Domain\Entities\JournalEntryLine;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
class EloquentJournalEntryLineRepository implements JournalEntryLineRepositoryInterface
{
    public function findByJournalEntry(string $tenantId, string $entryId): array
    {
        return JournalEntryLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('journal_entry_id', $entryId)
            ->orderBy('sequence')
            ->get()
            ->map(fn(JournalEntryLineModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function save(JournalEntryLine $line): void
    {
        /** @var JournalEntryLineModel $model */
        $model = JournalEntryLineModel::withoutGlobalScopes()->findOrNew($line->id);
        $model->fill([
            'tenant_id'        => $line->tenantId,
            'journal_entry_id' => $line->journalEntryId,
            'account_id'       => $line->accountId,
            'type'             => $line->type,
            'amount'           => $line->amount,
            'currency_code'    => $line->currencyCode,
            'description'      => $line->description,
            'sequence'         => $line->sequence,
        ]);
        if (! $model->exists) {
            $model->id = $line->id;
        }
        $model->save();
    }
    public function deleteByJournalEntry(string $tenantId, string $entryId): void
    {
        JournalEntryLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('journal_entry_id', $entryId)
            ->delete();
    }
    private function mapToEntity(JournalEntryLineModel $model): JournalEntryLine
    {
        return new JournalEntryLine(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            journalEntryId: (string) $model->journal_entry_id,
            accountId: (string) $model->account_id,
            type: (string) $model->type,
            amount: (float) $model->amount,
            currencyCode: (string) $model->currency_code,
            description: $model->description !== null ? (string) $model->description : null,
            sequence: (int) $model->sequence,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
