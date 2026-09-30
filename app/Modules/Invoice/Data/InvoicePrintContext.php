<?php

declare(strict_types=1);

namespace Modules\Invoice\Data;

use DateTimeImmutable;
use Modules\Invoice\Enums\InvoiceCopyType;

final readonly class InvoicePrintContext
{
    public function __construct(
        public DateTimeImmutable $printedAt,
        public string $printedBy,
        public ?InvoiceCopyType $copyType,
    ) {}
}
