<?php

declare(strict_types=1);

namespace Modules\HR\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HR\Entities\Employee;

/**
 * Employee Hired Event
 *
 * Fired when a new employee is hired.
 */
class EmployeeHired
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Employee $employee
    ) {}
}
