<?php

namespace App\Modules\Procurement\Events;

use App\Modules\Procurement\Models\Supplier;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Supplier $supplier;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }
}
