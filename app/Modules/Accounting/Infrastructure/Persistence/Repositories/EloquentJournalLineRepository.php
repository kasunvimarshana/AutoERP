<?php
namespace Modules\Accounting\Infrastructure\Persistence\Repositories;

use Modules\Accounting\Domain\Entities\JournalLine;
use Modules\Accounting\Domain\Repositories\JournalLineRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalLineModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentJournalLineRepository extends EloquentRepository implements JournalLineRepositoryInterface
{
    public function __construct(JournalLineModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?JournalLine
    {
        $model = $this->model->find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByEntry(int $entryId): array
    {
        return $this->model->where('journal_entry_id', $entryId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): JournalLine
    {
        $model = $this->model->create($data);
        return $this->toEntity($model);
    }

    public function delete(JournalLine $line): bool
    {
        $model = $this->model->findOrFail($line->id);
        return (bool) $model->delete();
    }

    private function toEntity(JournalLineModel $model): JournalLine
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
