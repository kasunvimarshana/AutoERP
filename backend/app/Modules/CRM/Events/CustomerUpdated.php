<?php

namespace App\Modules\CRM\Events;

use App\Modules\CRM\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Customer Updated Event
 * 
 * Fired when a customer's information is updated
 * 
 * @package App\Modules\CRM\Events
 */
class CustomerUpdated
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
