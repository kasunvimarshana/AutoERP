<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\SerialNumber;

class SerialNumberDeallocated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SerialNumber $serialNumber
    ) {}
}
