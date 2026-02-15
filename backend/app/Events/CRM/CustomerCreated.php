<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Modules\CRM\Models\Customer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly int $tenantId,
        public readonly int $userId
    ) {}
}
