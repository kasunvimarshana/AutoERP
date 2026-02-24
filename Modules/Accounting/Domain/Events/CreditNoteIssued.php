<?php

namespace Modules\Accounting\Domain\Events;

class CreditNoteIssued
{
    public function __construct(
        public readonly string $creditNoteId,
        public readonly string $tenantId,
        public readonly string $sourceInvoiceId,
        public readonly string $amount,
    ) {}
}
