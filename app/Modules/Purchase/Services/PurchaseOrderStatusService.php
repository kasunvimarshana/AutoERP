<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseOrderStatusService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseStatusService $transitions,
        private readonly PurchaseDocumentLockService $locks,
        private readonly PurchaseDocumentBlockerService $blockers,
    ) {}

    public function submit(PurchaseOrder $order, ?int $submittedBy = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $submittedBy): PurchaseOrder {
            $locked = $this->lock($order);
            $this->transitions->assertPurchaseOrderTransition(
                $locked->status,
                PurchaseOrderStatus::PendingApproval,
            );
            $locked->status = PurchaseOrderStatus::PendingApproval;
            $locked->submitted_by = $submittedBy;
            $locked->submitted_at = now();
            $locked->save();

            return $locked;
        });
    }

    public function approve(PurchaseOrder $order, ?int $approvedBy = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $approvedBy): PurchaseOrder {
            $locked = $this->lock($order);
            $this->transitions->assertPurchaseOrderTransition($locked->status, PurchaseOrderStatus::Approved);
            $locked->status = PurchaseOrderStatus::Approved;
            $locked->approved_by = $approvedBy;
            $locked->approved_at = now();
            $locked->save();

            return $locked;
        });
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order): PurchaseOrder {
            $locked = $this->lock($order, ['lines']);
            $this->assertCancellable($locked);
            $this->transitions->assertPurchaseOrderTransition($locked->status, PurchaseOrderStatus::Cancelled);
            $locked->status = PurchaseOrderStatus::Cancelled;
            $locked->save();

            return $locked;
        });
    }

    public function close(PurchaseOrder $order, ?int $closedBy = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $closedBy): PurchaseOrder {
            $locked = $this->lock($order, ['lines']);
            $this->transitions->assertPurchaseOrderTransition($locked->status, PurchaseOrderStatus::Closed);
            $this->assertClosable($locked);
            $locked->status = PurchaseOrderStatus::Closed;
            $locked->closed_by = $closedBy;
            $locked->closed_at = now();
            $locked->save();

            return $locked;
        });
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

    private function assertClosable(PurchaseOrder $order): void
    {
        $blocker = $this->blockers->purchaseOrderCloseBlocker($order, lockRelated: true);
        if ($blocker !== null) {
            throw new InvalidArgumentException($blocker['reason']);
        }
    }

    /**
     * @param  list<string>  $relations
     */
    private function lock(PurchaseOrder $order, array $relations = []): PurchaseOrder
    {
        $locked = $this->locks->purchaseOrders([(int) $order->getKey()])->first();
        if (! $locked instanceof PurchaseOrder) {
            throw new InvalidArgumentException('Purchase order was not found.');
        }

        if (in_array('lines', $relations, true)) {
            $lines = PurchaseOrderLine::query()
                ->where('purchase_order_id', $locked->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $locked->setRelation('lines', $lines);
        }

        return $locked;
    }

}
