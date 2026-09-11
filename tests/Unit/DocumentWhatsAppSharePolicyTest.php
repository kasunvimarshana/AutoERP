<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceWhatsAppShareService;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseOrderWhatsAppShareService;
use Tests\TestCase;

final class DocumentWhatsAppSharePolicyTest extends TestCase
{
    public function test_only_active_posted_customer_invoices_are_shareable(): void
    {
        $service = app(InvoiceWhatsAppShareService::class);
        $invoice = new Invoice;
        $invoice->forceFill([
            'party_id' => 10,
            'party_type' => 'customer',
            'direction' => InvoiceDirection::Outbound,
            'status' => InvoiceStatus::Posted,
        ]);

        self::assertTrue($service->isShareable($invoice));

        $invoice->status = InvoiceStatus::Cancelled;
        self::assertFalse($service->isShareable($invoice));

        $invoice->status = InvoiceStatus::Posted;
        $invoice->direction = InvoiceDirection::Inbound;
        self::assertFalse($service->isShareable($invoice));
    }

    public function test_only_approved_or_closed_purchase_orders_are_shareable(): void
    {
        $service = app(PurchaseOrderWhatsAppShareService::class);
        $order = new PurchaseOrder;
        $order->status = PurchaseOrderStatus::Approved;

        self::assertTrue($service->isShareable($order));

        $order->status = PurchaseOrderStatus::Closed;
        self::assertTrue($service->isShareable($order));

        $order->status = PurchaseOrderStatus::Cancelled;
        self::assertFalse($service->isShareable($order));
    }
}
