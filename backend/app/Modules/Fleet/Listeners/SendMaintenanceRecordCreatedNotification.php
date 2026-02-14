<?php

namespace App\Modules\Fleet\Listeners;

use App\Modules\Fleet\Events\MaintenanceRecordCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMaintenanceRecordCreatedNotification implements ShouldQueue
{
    public function handle(MaintenanceRecordCreated $event): void
    {
        // Implement notification logic here
    }
}
