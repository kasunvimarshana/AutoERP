<?php

declare(strict_types=1);

namespace App\Listeners\Invoice;

use App\Events\Invoice\InvoiceGenerated;
use App\Notifications\InvoiceGeneratedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendInvoiceToCustomer implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(InvoiceGenerated $event): void
    {
        $invoice = $event->invoice;
        
        // Load customer with contacts
        $invoice->load('customer.contacts');
        
        // Get primary contact or customer user
        $contacts = $invoice->customer->contacts()->where('is_primary', true)->get();
        
        if ($contacts->isEmpty()) {
            $contacts = $invoice->customer->contacts;
        }

        // Send notification to each contact
        foreach ($contacts as $contact) {
            if ($contact->email) {
                // For now, create a user notification
                // In production, this would send email via proper channel
                $user = \App\Models\User::where('email', $contact->email)
                    ->where('tenant_id', $event->tenantId)
                    ->first();
                    
                if ($user) {
                    $user->notify(new InvoiceGeneratedNotification($invoice));
                }
            }
        }
    }
}
