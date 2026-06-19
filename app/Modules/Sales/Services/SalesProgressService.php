<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Models\Invoice;
use Modules\Sales\Models\SalesInvoiceLink;
use Modules\Sales\Models\SalesOrder;

final class SalesProgressService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesFulfilmentBalanceService $balances,
    ) {}

    /**
     * @return array{allocation: string, delivery: string, invoice: string, payment: string, return: string}
     */
    public function forSalesOrder(SalesOrder $order): array
    {
        $order->loadMissing('lines');

        return [
            'allocation' => $this->balances->allocationStatus($order->lines),
            'delivery' => $this->balances->deliveryStatus($order->lines),
            'invoice' => $this->balances->salesOrderInvoiceStatus($order->lines),
            'payment' => $this->paymentStatusForOrder($order),
            'return' => $this->balances->salesOrderReturnStatus($order->lines),
        ];
    }

    private function paymentStatusForOrder(SalesOrder $order): string
    {
        $invoiceIds = $order->relationLoaded('invoiceLinks')
            ? $order->invoiceLinks->pluck('invoice_id')->all()
            : [];

        if ($invoiceIds === [] && $order->getKey() !== null) {
            $invoiceIds = SalesInvoiceLink::query()
                ->where('tenant_id', $order->tenant_id)
                ->where('source_type', 'sales_order')
                ->where('source_id', $order->getKey())
                ->pluck('invoice_id')
                ->all();
        }

        if ($invoiceIds === []) {
            return 'unpaid';
        }

        $invoices = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->where('direction', InvoiceDirection::Outbound)
            ->get(['id', 'grand_total', 'paid_total', 'credit_total', 'balance_due']);

        $total = '0.000000';
        $paid = '0.000000';
        foreach ($invoices as $invoice) {
            $total = $this->math->add($total, (string) $invoice->grand_total);
            $paid = $this->math->add($paid, (string) $invoice->paid_total);
            $paid = $this->math->add($paid, (string) $invoice->credit_total);
        }

        if ($this->math->compare($paid, '0.000000') <= 0 || $this->math->compare($total, '0.000000') <= 0) {
            return 'unpaid';
        }

        return $this->math->compare($paid, $total) >= 0 ? 'paid' : 'partial';
    }
}
