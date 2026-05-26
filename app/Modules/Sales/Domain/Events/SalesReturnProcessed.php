<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Events;

final readonly class SalesReturnProcessed
{
    public function __construct(public int $salesReturnId)
    {
    }
}
