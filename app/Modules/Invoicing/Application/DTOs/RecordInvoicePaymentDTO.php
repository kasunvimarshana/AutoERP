<?php

declare(strict_types=1);

namespace Modules\Invoicing\Application\DTOs;

class RecordInvoicePaymentDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $amount,
    ) {
    }
}
