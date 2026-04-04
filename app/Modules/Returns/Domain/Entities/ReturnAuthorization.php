<?php

namespace Modules\Returns\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class ReturnAuthorization extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $rmaNumber,
        public readonly int $stockReturnId,
        public readonly string $status,
        public readonly ?\DateTimeImmutable $expiresAt = null,
        public readonly ?int $approvedBy = null,
        public readonly ?\DateTimeImmutable $approvedAt = null,
        public readonly ?string $notes = null,
    ) {
        parent::__construct($id);
    }
}
