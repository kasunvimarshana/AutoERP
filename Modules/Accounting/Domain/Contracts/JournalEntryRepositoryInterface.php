<?php

namespace Modules\Accounting\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface JournalEntryRepositoryInterface extends RepositoryInterface
{
    public function nextNumber(string $tenantId): string;
    public function paginate(array $filters, int $perPage = 15): object;
}
