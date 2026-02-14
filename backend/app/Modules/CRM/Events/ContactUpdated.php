<?php

namespace App\Modules\CRM\Events;

use App\Modules\CRM\Models\Contact;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Contact Updated Event
 * 
 * Fired when a contact's information is updated
 * 
 * @package App\Modules\CRM\Events
 */
class ContactUpdated
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
