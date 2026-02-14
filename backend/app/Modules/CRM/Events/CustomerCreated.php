<?php

namespace App\Modules\CRM\Events;

use App\Modules\CRM\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Customer Created Event
 * 
 * Fired when a new customer is created in the system
 * 
 * @package App\Modules\CRM\Events
 */
class CustomerCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param Customer $customer
     */
    public function __construct(public Customer $customer)
    {
    }
}
