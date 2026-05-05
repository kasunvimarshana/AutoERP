<?php

declare(strict_types=1);

namespace Modules\Invoicing\Domain\Entities;

use DateTimeImmutable;
use Modules\Invoicing\Domain\ValueObjects\InvoiceEntityType;
use Modules\Invoicing\Domain\ValueObjects\InvoiceStatus;
use Modules\Invoicing\Domain\ValueObjects\InvoiceType;

class Invoice
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $orgUnitId,
        public readonly int $rowVersion,
        public readonly string $invoiceNumber,
        public readonly InvoiceType $invoiceType,
        public readonly InvoiceEntityType $entityType,
        public readonly ?string $entityId,
        public readonly InvoiceStatus $status,
        public readonly DateTimeImmutable $issueDate,
        public readonly DateTimeImmutable $dueDate,
        public readonly string $subtotalAmount,
        public readonly string $taxAmount,
        public readonly string $totalAmount,
        public readonly string $paidAmount,
        public readonly string $balanceAmount,
        public readonly string $currency,
        public readonly ?string $notes,
        public readonly ?array $metadata,
        public readonly bool $isActive,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }
}
