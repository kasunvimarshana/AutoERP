<?php

namespace App\Modules\POS\Listeners;

use App\Modules\POS\Events\InvoiceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOfInvoice implements ShouldQueue
{
    public function handle(InvoiceCreated $event): void
    {
        // Send notification to customer about new invoice

        \Log::info('Invoice Created Notification', [
            'invoice_number' => $event->invoice->invoice_number,
            'customer_id' => $event->invoice->customer_id,
            'total_amount' => $event->invoice->total_amount,
            'due_date' => $event->invoice->due_date,
        ]);

        // Future: Send invoice PDF via email
        // Future: Create notification record in database
        // Future: Send reminder for due date
    }
}
