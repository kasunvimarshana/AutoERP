<?php

namespace App\Modules\CRM\Events;

use App\Modules\CRM\Models\Contact;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Contact Created Event
 * 
 * Fired when a new contact is created in the system
 * 
 * @package App\Modules\CRM\Events
 */
class ContactCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param Contact $contact
     */
    public function __construct(public Contact $contact)
    {
    }
}
