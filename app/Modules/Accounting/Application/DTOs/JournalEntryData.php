<?php
namespace Modules\Accounting\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class JournalEntryData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $referenceNumber,
        public readonly string $entryDate,
        public readonly array $lines = [],
        public readonly ?string $description = null,
        public readonly ?string $sourceType = null,
        public readonly ?int $sourceId = null,
    ) {}
}
