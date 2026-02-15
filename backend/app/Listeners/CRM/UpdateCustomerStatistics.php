<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\CustomerCreated;
use App\Events\Sales\OrderCreated;
use App\Events\Invoice\InvoicePaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class UpdateCustomerStatistics implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CustomerCreated|OrderCreated|InvoicePaymentReceived $event): void
    {
        $customerId = match (true) {
            $event instanceof CustomerCreated => $event->customer->id,
            $event instanceof OrderCreated => $event->order->customer_id,
            $event instanceof InvoicePaymentReceived => $event->invoice->customer_id,
            default => null,
        };

        if (!$customerId) {
            return;
        }

        DB::transaction(function () use ($customerId, $event) {
            $customer = \App\Modules\CRM\Models\Customer::find($customerId);
            
            if (!$customer) {
                return;
            }

            // Calculate statistics
            $totalOrders = $customer->salesOrders()->count();
            $totalSpent = $customer->salesOrders()
                ->where('status', 'completed')
                ->sum('total_amount');
            
            $totalPaid = $customer->invoices()
                ->where('status', 'paid')
                ->sum('total_amount');
            
            $outstandingBalance = $customer->invoices()
                ->whereIn('status', ['pending', 'partial'])
                ->sum('balance_due');

            // Update customer statistics
            $customer->update([
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'total_paid' => $totalPaid,
                'outstanding_balance' => $outstandingBalance,
                'last_order_date' => $customer->salesOrders()->latest()->first()?->order_date,
                'last_payment_date' => $customer->invoices()
                    ->where('status', 'paid')
                    ->latest('paid_at')
                    ->first()?->paid_at,
            ]);
        });
    }
}
