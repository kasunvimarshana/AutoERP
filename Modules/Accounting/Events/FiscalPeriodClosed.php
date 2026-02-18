<?php

declare(strict_types=1);

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Entities\FiscalPeriod;

/**
 * Fiscal Period Closed Event
 *
 * Dispatched when a fiscal period is closed.
 */
class FiscalPeriodClosed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public FiscalPeriod $fiscalPeriod
    ) {}
}
