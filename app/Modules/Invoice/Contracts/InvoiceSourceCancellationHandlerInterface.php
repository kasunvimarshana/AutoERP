<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

use Modules\Invoice\Data\InvoiceSourceCancellationContext;

interface InvoiceSourceCancellationHandlerInterface
{
    public const TAG = 'invoice.source_cancellation_handler';

    public function supports(InvoiceSourceCancellationContext $context): bool;

    public function restore(InvoiceSourceCancellationContext $context): void;
}
