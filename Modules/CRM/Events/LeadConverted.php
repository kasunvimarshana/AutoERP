<?php

declare(strict_types=1);

namespace Modules\CRM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\CRM\Models\Customer;
use Modules\CRM\Models\Lead;

class LeadConverted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public Customer $customer
    ) {}
}
