<?php

namespace App\Modules\Reporting\Events;

use App\Modules\Reporting\Models\ReportExecution;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Report Executed Event
 *
 * Fired when a report is successfully executed.
 */
class ReportExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ReportExecution $execution,
        public array $results,
    ) {}
}
