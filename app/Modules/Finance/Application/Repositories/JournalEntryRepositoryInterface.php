<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface JournalEntryRepositoryInterface extends RepositoryPortInterface
{
    public function findByEntryNumber(int $tenantId, string $entryNumber): ?DataRecord;
}
