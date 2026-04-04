<?php
namespace Modules\Accounting\Infrastructure\Persistence\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalLine;
use Modules\Accounting\Domain\Repositories\JournalEntryRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalLineModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentJournalEntryRepository extends EloquentRepository implements JournalEntryRepositoryInterface
{
    public function __construct(
        JournalEntryModel $model,
        private readonly JournalLineModel $lineModel,
    ) {
        parent::__construct($model);
    }

    public function findById(int $id): ?JournalEntry
    {
        $model = $this->model->find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByReference(int $tenantId, string $ref): ?JournalEntry
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('reference_number', $ref)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): JournalEntry
    {
        $model = $this->model->create($data);
        return $this->toEntity($model);
    }

    public function addLine(int $entryId, array $lineData): JournalLine
    {
        $model = $this->lineModel->create(array_merge($lineData, ['journal_entry_id' => $entryId]));
        return $this->toLineEntity($model);
    }

    public function findLines(int $entryId): array
    {
        return $this->lineModel->where('journal_entry_id', $entryId)
            ->get()
            ->map(fn($m) => $this->toLineEntity($m))
            ->all();
    }

    public function update(JournalEntry $entry, array $data): JournalEntry
    {
        $model = $this->model->findOrFail($entry->id);
        $model->fill($data)->save();
        return $this->toEntity($model);
    }

    public function delete(JournalEntry $entry): bool
    {
        $model = $this->model->findOrFail($entry->id);
        return (bool) $model->delete();
    }

    private function toEntity(JournalEntryModel $model): JournalEntry
    {
        return new JournalEntry(
            id:              $model->id,
            tenantId:        $model->tenant_id,
            referenceNumber: $model->reference_number,
            status:          $model->status,
            entryDate:       $model->entry_date instanceof \Carbon\Carbon
                                 ? $model->entry_date->toDateString()
                                 : (string) $model->entry_date,
            description:     $model->description,
            sourceType:      $model->source_type,
            sourceId:        $model->source_id,
            postedBy:        $model->posted_by,
            postedAt:        $model->posted_at?->toDateTimeString(),
            reversedBy:      $model->reversed_by,
            reversedAt:      $model->reversed_at?->toDateTimeString(),
        );
    }

    private function toLineEntity(JournalLineModel $model): JournalLine
    {
        return new JournalLine(
            id:             $model->id,
            journalEntryId: $model->journal_entry_id,
            accountId:      $model->account_id,
            debit:          (float) $model->debit,
            credit:         (float) $model->credit,
            currency:       $model->currency,
            description:    $model->description,
        );
    }
}
