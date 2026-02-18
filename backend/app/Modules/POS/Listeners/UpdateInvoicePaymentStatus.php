<?php

namespace App\Modules\POS\Listeners;

use App\Modules\POS\Events\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateInvoicePaymentStatus implements ShouldQueue
{
    public function handle(PaymentReceived $event): void
    {
        // Invoice payment status is already updated in PaymentService
        // This listener can handle additional async tasks

        \Log::info('Invoice Payment Status Updated', [
            'invoice_id' => $event->payment->invoice_id,
            'payment_amount' => $event->payment->amount,
        ]);

        // Future: Update analytics and reports
        // Future: Trigger accounting entries
        // Future: Update customer credit status
    }
}
