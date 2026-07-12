<?php

declare(strict_types=1);

namespace Modules\Invoice\Data;

use InvalidArgumentException;
use Modules\Invoice\Enums\InvoiceStatus;

final readonly class InvoiceSourceRestorationContext
{
    /** @param list<InvoiceSourceLineSnapshot> $sourceLines */
    public function __construct(
        public int $invoiceId,
        public int $tenantId,
        public ?int $organizationUnitId,
        public InvoiceStatus $terminalStatus,
        public array $sourceLines,
    ) {
        if (! in_array($terminalStatus, [
            InvoiceStatus::Cancelled,
            InvoiceStatus::Void,
            InvoiceStatus::Reversed,
        ], true)) {
            throw new InvalidArgumentException('Invoice source restoration requires a terminal invoice status.');
        }
    }

    public function isReversal(): bool
    {
        return $this->terminalStatus === InvoiceStatus::Reversed;
    }

    public function linkStatus(): string
    {
        return $this->isReversal() ? InvoiceStatus::Reversed->value : InvoiceStatus::Cancelled->value;
    }
}
