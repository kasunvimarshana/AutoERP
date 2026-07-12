<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

use Modules\Invoice\Data\InvoiceSourceRestorationContext;

interface InvoiceSourceRestorationHandlerInterface
{
    public const TAG = 'invoice.source_restoration_handler';

    public function supports(InvoiceSourceRestorationContext $context): bool;

    public function restore(InvoiceSourceRestorationContext $context): void;
}
