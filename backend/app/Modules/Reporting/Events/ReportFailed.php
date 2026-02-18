<?php

namespace App\Modules\Reporting\Events;

use App\Modules\Reporting\Models\ReportExecution;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Report Failed Event
 *
 * Fired when a report execution fails.
 */
class ReportFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ReportExecution $execution,
        public string $errorMessage,
    ) {}
}
