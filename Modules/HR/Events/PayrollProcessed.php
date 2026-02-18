<?php

declare(strict_types=1);

namespace Modules\HR\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HR\Entities\Payroll;

/**
 * Payroll Processed Event
 *
 * Fired when payroll is calculated and processed.
 */
class PayrollProcessed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Payroll $payroll
    ) {}
}
