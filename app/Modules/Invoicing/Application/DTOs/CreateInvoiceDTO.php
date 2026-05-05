<?php

declare(strict_types=1);

namespace Modules\Invoicing\Application\DTOs;

use DateTimeImmutable;
use Modules\Invoicing\Domain\ValueObjects\InvoiceEntityType;
use Modules\Invoicing\Domain\ValueObjects\InvoiceType;

class CreateInvoiceDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $orgUnitId,
        public readonly string $invoiceNumber,
        public readonly InvoiceType $invoiceType,
        public readonly InvoiceEntityType $entityType,
        public readonly ?string $entityId,
        public readonly DateTimeImmutable $issueDate,
        public readonly DateTimeImmutable $dueDate,
        public readonly string $subtotalAmount,
        public readonly string $taxAmount,
        public readonly string $totalAmount,
        public readonly string $currency,
        public readonly ?string $notes,
        public readonly ?array $metadata,
    ) {
    }
}
