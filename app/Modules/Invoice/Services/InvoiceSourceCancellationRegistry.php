<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use LogicException;
use Modules\Invoice\Contracts\InvoiceSourceCancellationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceCancellationContext;

final class InvoiceSourceCancellationRegistry
{
    /**
     * @param iterable<InvoiceSourceCancellationHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {}

    public function restore(InvoiceSourceCancellationContext $context): void
    {
        foreach ($this->handlers as $handler) {
            if (! $handler instanceof InvoiceSourceCancellationHandlerInterface) {
                throw new LogicException('Invoice source cancellation handler registry contains an invalid service.');
            }

            if ($handler->supports($context)) {
                $handler->restore($context);
            }
        }
    }
}
