<?php

namespace App\Modules\POS\Listeners;

use App\Modules\POS\Events\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOfPaymentReceipt implements ShouldQueue
{
    public function handle(PaymentReceived $event): void
    {
        // Send payment receipt to customer

        \Log::info('Payment Received Notification', [
            'payment_number' => $event->payment->payment_number,
            'customer_id' => $event->payment->customer_id,
            'amount' => $event->payment->amount,
            'payment_method' => $event->payment->payment_method->value,
        ]);

        // Future: Generate and send payment receipt PDF
        // Future: Update customer account statement
        // Future: Send thank you notification
    }
}
