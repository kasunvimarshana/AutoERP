<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\Customer;

class CustomerCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Customer $customer) {}
}
