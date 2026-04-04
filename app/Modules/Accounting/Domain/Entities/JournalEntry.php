<?php
namespace Modules\Accounting\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class JournalEntry extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $referenceNumber,
        public readonly string $status,
        public readonly string $entryDate,
        public readonly ?string $description = null,
        public readonly ?string $sourceType = null,
        public readonly ?int $sourceId = null,
        public readonly ?int $postedBy = null,
        public readonly ?string $postedAt = null,
        public readonly ?int $reversedBy = null,
        public readonly ?string $reversedAt = null,
    ) {
        parent::__construct($id);
    }
}
