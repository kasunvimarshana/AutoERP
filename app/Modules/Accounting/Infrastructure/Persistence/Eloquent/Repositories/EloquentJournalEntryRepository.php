<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
class EloquentJournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?JournalEntry
    {
        $model = JournalEntryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function findByNumber(string $tenantId, string $number): ?JournalEntry
    {
        $model = JournalEntryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('number', $number)
            ->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function findAll(string $tenantId, array $filters = []): array
    {
        $query = JournalEntryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['from_date'])) {
            $query->whereDate('date', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->whereDate('date', '<=', $filters['to_date']);
        }
        return $query->orderByDesc('date')
            ->get()
            ->map(fn(JournalEntryModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function save(JournalEntry $entry): void
    {
        /** @var JournalEntryModel $model */
        $model = JournalEntryModel::withoutGlobalScopes()->findOrNew($entry->id);
        $model->fill([
            'tenant_id'   => $entry->tenantId,
            'number'      => $entry->number,
            'date'        => $entry->date->format('Y-m-d'),
            'description' => $entry->description,
            'reference'   => $entry->reference,
            'status'      => $entry->status,
            'source_type' => $entry->sourceType,
            'source_id'   => $entry->sourceId,
            'posted_at'   => $entry->postedAt?->format('Y-m-d H:i:s'),
            'voided_at'   => $entry->voidedAt?->format('Y-m-d H:i:s'),
        ]);
        if (! $model->exists) {
            $model->id = $entry->id;
        }
        $model->save();
    }
    public function delete(string $tenantId, string $id): void
    {
        JournalEntryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }
    private function mapToEntity(JournalEntryModel $model): JournalEntry
    {
        return new JournalEntry(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            number: (string) $model->number,
            date: $model->date ?? now(),
            description: (string) $model->description,
            reference: $model->reference !== null ? (string) $model->reference : null,
            status: (string) $model->status,
            sourceType: $model->source_type !== null ? (string) $model->source_type : null,
            sourceId: $model->source_id !== null ? (string) $model->source_id : null,
            postedAt: $model->posted_at,
            voidedAt: $model->voided_at,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
