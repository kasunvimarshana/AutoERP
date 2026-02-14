<?php

namespace App\Modules\Fleet\Events;

use App\Modules\Fleet\Models\MaintenanceRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceRecordCreated
{
    use Dispatchable, SerializesModels;

    public MaintenanceRecord $maintenanceRecord;

    public function __construct(MaintenanceRecord $maintenanceRecord)
    {
        $this->maintenanceRecord = $maintenanceRecord;
    }
}
