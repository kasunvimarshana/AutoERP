<?php

namespace App\Modules\Reporting\Events;

use App\Modules\Reporting\Models\Dashboard;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dashboard Updated Event
 *
 * Fired when a dashboard is updated.
 */
class DashboardUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Dashboard $dashboard,
    ) {}
}
