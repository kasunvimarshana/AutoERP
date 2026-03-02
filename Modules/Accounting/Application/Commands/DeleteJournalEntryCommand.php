<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Commands;

final readonly class DeleteJournalEntryCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
