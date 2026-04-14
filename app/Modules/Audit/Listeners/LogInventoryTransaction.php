<?php

namespace App\Modules\Audit\Listeners;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Inventory\Events\InventoryTransactionCreated;

class LogInventoryTransaction
{
    public function handle(InventoryTransactionCreated $event)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'created',
            'auditable_type' => get_class($event->transaction),
            'auditable_id' => $event->transaction->id,
            'old_values' => null,
            'new_values' => $event->transaction->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}