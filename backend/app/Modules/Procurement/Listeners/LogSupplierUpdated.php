<?php

namespace App\Modules\Procurement\Listeners;

use App\Modules\Procurement\Events\SupplierUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogSupplierUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     *
     * @param SupplierUpdated $event
     * @return void
     */
    public function handle(SupplierUpdated $event): void
    {
        Log::info('Supplier updated', [
            'supplier_id' => $event->supplier->id,
            'supplier_name' => $event->supplier->name,
            'tenant_id' => $event->supplier->tenant_id,
        ]);
    }
}
