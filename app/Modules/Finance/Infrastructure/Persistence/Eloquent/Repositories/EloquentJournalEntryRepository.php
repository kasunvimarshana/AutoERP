<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;

final class EloquentJournalEntryRepository extends FinanceRepository implements JournalEntryRepositoryInterface
{
    public function __construct(JournalEntryModel $model)
    {
        parent::__construct($model);
    }

    public function findByEntryNumber(int $tenantId, string $entryNumber): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('entry_number', $entryNumber)
            ->first();

        return $model !== null ? $this->toRecord($model) : null;
    }
}
