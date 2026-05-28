<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;

final class EloquentJournalEntryLineRepository extends FinanceRepository implements JournalEntryLineRepositoryInterface
{
    public function __construct(JournalEntryLineModel $model)
    {
        parent::__construct($model);
    }

    /** @return list<DataRecord> */
    public function listByJournalEntry(int $journalEntryId): array
    {
        return $this->list(['journal_entry_id' => $journalEntryId]);
    }

    public function nextLineNumber(int $tenantId, int $journalEntryId): int
    {
        $max = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('journal_entry_id', $journalEntryId)
            ->max('line_number');

        return ((int) $max) + 1;
    }
}
