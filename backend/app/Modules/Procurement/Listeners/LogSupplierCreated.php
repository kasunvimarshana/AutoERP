<?php

namespace App\Modules\Procurement\Listeners;

use App\Modules\Procurement\Events\SupplierCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogSupplierCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     *
     * @param SupplierCreated $event
     * @return void
     */
    public function handle(SupplierCreated $event): void
    {
        Log::info('Supplier created', [
            'supplier_id' => $event->supplier->id,
            'supplier_name' => $event->supplier->name,
            'tenant_id' => $event->supplier->tenant_id,
        ]);
    }
}
