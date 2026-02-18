<?php

namespace App\Modules\POS\Listeners;

use App\Modules\POS\Events\QuotationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOfQuotation implements ShouldQueue
{
    public function handle(QuotationCreated $event): void
    {
        // Send quotation to customer

        \Log::info('Quotation Created Notification', [
            'quotation_number' => $event->quotation->quotation_number,
            'customer_id' => $event->quotation->customer_id,
            'total_amount' => $event->quotation->total_amount,
            'valid_until' => $event->quotation->valid_until,
        ]);

        // Future: Generate and send quotation PDF
        // Future: Send reminder before expiry
        // Future: Track customer engagement with quotation
    }
}
