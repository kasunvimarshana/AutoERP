<?php

namespace Modules\Purchase\Domain\Entities;

class PurchaseRequisition
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenantId,
        public readonly string  $number,
        public readonly string  $requestedBy,
        public readonly string  $status,
        public readonly array   $lines,
        public readonly string  $totalAmount,
        public readonly ?string $department,
        public readonly ?string $requiredBy,
        public readonly ?string $notes,
        public readonly ?string $approvedBy,
        public readonly ?\DateTimeImmutable $approvedAt,
        public readonly ?string $rejectedBy,
        public readonly ?string $rejectionReason,
        public readonly ?\DateTimeImmutable $rejectedAt,
    ) {}
}
