<?php

namespace App\Modules\Reporting\Events;

use App\Modules\Reporting\Models\ScheduledReport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Report Scheduled Event
 *
 * Fired when a report is scheduled.
 */
class ReportScheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ScheduledReport $scheduledReport,
    ) {}
}
