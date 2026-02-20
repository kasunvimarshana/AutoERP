<?php

namespace App\Listeners;

use App\Enums\AuditAction;
use App\Events\InvoiceCreated;
use App\Events\OrderCreated;
use App\Events\PaymentRecorded;
use App\Events\ProductCreated;
use App\Events\StockAdjusted;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Events\Dispatcher;

class AuditEventSubscriber
{
    public function handleOrderCreated(OrderCreated $event): void
    {
        $this->record(
            tenantId: $event->order->tenant_id,
            action: AuditAction::Created,
            auditableType: Order::class,
            auditableId: $event->order->id,
            newValues: [
                'order_number' => $event->order->order_number,
                'total' => $event->order->total,
                'status' => $event->order->status?->value,
            ]
        );
    }

    public function handleInvoiceCreated(InvoiceCreated $event): void
    {
        $this->record(
            tenantId: $event->invoice->tenant_id,
            action: AuditAction::Created,
            auditableType: Invoice::class,
            auditableId: $event->invoice->id,
            newValues: [
                'invoice_number' => $event->invoice->invoice_number,
                'total' => $event->invoice->total,
                'status' => $event->invoice->status?->value,
            ]
        );
    }

    public function handlePaymentRecorded(PaymentRecorded $event): void
    {
        $this->record(
            tenantId: $event->payment->tenant_id,
            action: AuditAction::Created,
            auditableType: Payment::class,
            auditableId: $event->payment->id,
            newValues: [
                'payment_number' => $event->payment->payment_number,
                'amount' => $event->payment->amount,
                'invoice_id' => $event->payment->invoice_id,
            ]
        );
    }

    public function handleProductCreated(ProductCreated $event): void
    {
        $this->record(
            tenantId: $event->product->tenant_id,
            action: AuditAction::Created,
            auditableType: Product::class,
            auditableId: $event->product->id,
            newValues: [
                'name' => $event->product->name,
                'sku' => $event->product->sku,
                'type' => $event->product->type?->value,
            ]
        );
    }

    public function handleStockAdjusted(StockAdjusted $event): void
    {
        $this->record(
            tenantId: $event->movement->tenant_id,
            action: AuditAction::Updated,
            auditableType: StockMovement::class,
            auditableId: $event->movement->id,
            newValues: [
                'product_id' => $event->movement->product_id,
                'warehouse_id' => $event->movement->warehouse_id,
                'movement_type' => $event->movement->movement_type,
                'quantity' => $event->movement->quantity,
            ]
        );
    }

    private function record(
        ?string $tenantId,
        AuditAction $action,
        string $auditableType,
        string $auditableId,
        array $newValues = []
    ): void {
        AuditLog::create([
            'tenant_id' => $tenantId,
            'organization_id' => null,
            'user_id' => null,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => null,
            'new_values' => $newValues,
            'ip_address' => null,
            'user_agent' => null,
            'metadata' => ['source' => 'domain_event'],
        ]);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderCreated::class => 'handleOrderCreated',
            InvoiceCreated::class => 'handleInvoiceCreated',
            PaymentRecorded::class => 'handlePaymentRecorded',
            ProductCreated::class => 'handleProductCreated',
            StockAdjusted::class => 'handleStockAdjusted',
        ];
    }
}
