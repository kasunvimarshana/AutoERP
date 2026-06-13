<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;

final class SalesOrderStatusService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesStatusService $statuses,
    ) {}

    public function submit(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        $this->statuses->transition($order, SalesOrderStatus::PendingApproval, $userId);

        return $order;
    }

    public function approve(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        $this->statuses->transition($order, SalesOrderStatus::Approved, $userId);
        $order->approved_by = $userId;
        $order->approved_at = now();
        $order->save();

        return $order;
    }

    public function cancel(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        $this->assertCancellable($order);
        $this->statuses->transition($order, SalesOrderStatus::Cancelled, $userId);
        $order->cancelled_by = $userId;
        $order->cancelled_at = now();
        $order->save();

        return $order;
    }

    public function close(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        $this->statuses->transition($order, SalesOrderStatus::Closed, $userId);
        $order->closed_by = $userId;
        $order->closed_at = now();
        $order->save();

        return $order;
    }

    private function assertCancellable(SalesOrder $order): void
    {
        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if ($this->math->compare((string) $line->delivered_quantity, '0.000000') > 0
                || $this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
                throw new InvalidArgumentException(
                    'Sales orders with delivered or invoiced quantities cannot be cancelled.',
                );
            }
        }
    }
}
