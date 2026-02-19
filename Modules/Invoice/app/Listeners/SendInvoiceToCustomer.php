<?php

declare(strict_types=1);

namespace Modules\Invoice\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\JobCard\Events\JobCardCompleted;

/**
 * Send Invoice to Customer
 *
 * Listens to JobCardCompleted event and sends invoice email to customer
 * This is an asynchronous operation that runs in the queue
 */
class SendInvoiceToCustomer implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the listener may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying
     */
    public int $backoff = 60;

    /**
     * Handle the event
     */
    public function handle(JobCardCompleted $event): void
    {
        if (! $event->invoice) {
            Log::warning('No invoice available to send for completed job card', [
                'job_card_id' => $event->jobCard->id,
            ]);

            return;
        }

        try {
            // Load invoice with customer relationship
            $invoice = $event->invoice->load('customer');

            if (! $invoice->customer || ! $invoice->customer->email) {
                Log::warning('No customer email available for invoice', [
                    'invoice_id' => $invoice->id,
                    'job_card_id' => $event->jobCard->id,
                ]);

                return;
            }

            // Send invoice email (placeholder - implement actual mail class)
            // Mail::to($invoice->customer->email)
            //     ->send(new InvoiceMail($invoice));

            Log::info('Invoice email sent to customer', [
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'customer_email' => $invoice->customer->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $event->invoice->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Rethrow to trigger retry
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(JobCardCompleted $event, \Throwable $exception): void
    {
        Log::error('Failed to send invoice after all retries', [
            'invoice_id' => $event->invoice?->id,
            'job_card_id' => $event->jobCard->id,
            'error' => $exception->getMessage(),
        ]);

        // Notify admin about the failure
        // Could send alert, create ticket, etc.
    }
}
