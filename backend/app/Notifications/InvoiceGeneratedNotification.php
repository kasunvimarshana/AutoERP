<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Modules\Invoice\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'invoice_generated',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'customer_name' => $this->invoice->customer?->name ?? 'Unknown',
            'total_amount' => $this->invoice->total_amount,
            'currency_code' => $this->invoice->currency_code,
            'due_date' => $this->invoice->due_date?->toDateString(),
            'title' => 'Invoice Generated',
            'message' => "Invoice #{$this->invoice->invoice_number} has been generated for {$this->invoice->customer?->name}. Amount: {$this->invoice->currency_code} {$this->invoice->total_amount}",
            'action_url' => "/invoices/{$this->invoice->id}",
            'severity' => 'info',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
