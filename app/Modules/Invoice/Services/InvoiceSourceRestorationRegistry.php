<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use LogicException;
use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceRestorationContext;

final class InvoiceSourceRestorationRegistry
{
    /** @param iterable<InvoiceSourceRestorationHandlerInterface> $handlers */
    public function __construct(private readonly iterable $handlers) {}

    public function restore(InvoiceSourceRestorationContext $context): void
    {
        foreach ($this->handlers as $handler) {
            if (! $handler instanceof InvoiceSourceRestorationHandlerInterface) {
                throw new LogicException('Invoice source restoration handler registry contains an invalid service.');
            }

            if ($handler->supports($context)) {
                $handler->restore($context);
            }
        }
    }
}
