<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

use Modules\Core\DTOs\DataRecord;

final readonly class AuditCursorPage
{
    /** @param list<DataRecord> $items */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
        public int $perPage,
    ) {}
}
