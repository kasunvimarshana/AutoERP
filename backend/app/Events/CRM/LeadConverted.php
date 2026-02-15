<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\Customer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadConverted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly Customer $customer,
        public readonly int $tenantId,
        public readonly int $userId
    ) {}
}
