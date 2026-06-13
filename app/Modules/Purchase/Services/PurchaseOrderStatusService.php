<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;

final class PurchaseOrderStatusService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseStatusService $transitions,
    ) {}

    public function submit(PurchaseOrder $order, ?int $submittedBy = null): PurchaseOrder
    {
        $this->transitions->assertPurchaseOrderTransition(
            $order->status,
            PurchaseOrderStatus::PendingApproval,
        );
        $order->status = PurchaseOrderStatus::PendingApproval;
        $order->submitted_by = $submittedBy;
        $order->submitted_at = now();
        $order->save();

        return $order;
    }

    public function approve(PurchaseOrder $order, ?int $approvedBy = null): PurchaseOrder
    {
        $this->transitions->assertPurchaseOrderTransition($order->status, PurchaseOrderStatus::Approved);
        $order->status = PurchaseOrderStatus::Approved;
        $order->approved_by = $approvedBy;
        $order->approved_at = now();
        $order->save();

        return $order;
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        $this->assertCancellable($order);
        $this->transitions->assertPurchaseOrderTransition($order->status, PurchaseOrderStatus::Cancelled);
        $order->status = PurchaseOrderStatus::Cancelled;
        $order->save();

        return $order;
    }

    public function close(PurchaseOrder $order, ?int $closedBy = null): PurchaseOrder
    {
        $this->transitions->assertPurchaseOrderTransition($order->status, PurchaseOrderStatus::Closed);
        $order->status = PurchaseOrderStatus::Closed;
        $order->closed_by = $closedBy;
        $order->closed_at = now();
        $order->save();

        return $order;
    }

    private function assertCancellable(PurchaseOrder $order): void
    {
        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if ($this->math->compare((string) $line->received_quantity, '0.000000') > 0
                || $this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
                throw new InvalidArgumentException(
                    'Purchase orders with received or invoiced quantities cannot be cancelled.',
                );
            }
        }
    }
}
