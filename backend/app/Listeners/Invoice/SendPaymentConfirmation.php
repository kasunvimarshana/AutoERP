<?php

declare(strict_types=1);

namespace App\Listeners\Invoice;

use App\Events\Invoice\InvoicePaymentReceived;
use App\Notifications\PaymentReceivedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(InvoicePaymentReceived $event): void
    {
        $invoice = $event->invoice;
        $payment = $event->payment;
        
        // Load customer with contacts
        $invoice->load('customer.contacts');
        
        // Get primary contact
        $contacts = $invoice->customer->contacts()->where('is_primary', true)->get();
        
        if ($contacts->isEmpty()) {
            $contacts = $invoice->customer->contacts;
        }

        // Send payment confirmation to customer contacts
        foreach ($contacts as $contact) {
            if ($contact->email) {
                $user = User::where('email', $contact->email)
                    ->where('tenant_id', $event->tenantId)
                    ->first();
                    
                if ($user) {
                    $user->notify(new PaymentReceivedNotification($invoice, $payment));
                }
            }
        }
        
        // Also notify internal staff
        $staffUsers = User::where('tenant_id', $event->tenantId)
            ->permission('receive-payment-notifications')
            ->get();
            
        foreach ($staffUsers as $user) {
            $user->notify(new PaymentReceivedNotification($invoice, $payment));
        }
    }
}
