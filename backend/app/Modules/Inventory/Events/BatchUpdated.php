<?php

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\Batch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Batch $batch;

    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
    }
}
