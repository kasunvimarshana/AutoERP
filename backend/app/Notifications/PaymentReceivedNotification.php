<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'payment_id' => $this->payment->id,
            'payment_reference' => $this->payment->reference_number,
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->payment->amount,
            'currency_code' => $this->payment->currency_code,
            'payment_method' => $this->payment->payment_method,
            'payment_date' => $this->payment->payment_date->toDateString(),
            'title' => 'Payment Received',
            'message' => "Payment of {$this->payment->currency_code} {$this->payment->amount} received for Invoice #{$this->invoice->invoice_number}. Reference: {$this->payment->reference_number}",
            'action_url' => "/payments/{$this->payment->id}",
            'severity' => 'success',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
