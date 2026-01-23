<?php

namespace App\Modules\CRMManagement\Events;

use App\Modules\CRMManagement\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationScheduled
{
    use Dispatchable, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }
}
