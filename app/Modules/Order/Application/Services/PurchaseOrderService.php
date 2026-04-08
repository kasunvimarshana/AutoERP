<?php

declare(strict_types=1);

namespace Modules\Order\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Order\Application\Contracts\PurchaseOrderServiceInterface;
use Modules\Order\Domain\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Order\Domain\Events\PurchaseOrderCreated;
use Modules\Order\Domain\Exceptions\InvalidOrderStatusException;
use Modules\Order\Domain\Exceptions\PurchaseOrderNotFoundException;

class PurchaseOrderService extends BaseService implements PurchaseOrderServiceInterface
{
    public function __construct(PurchaseOrderRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler.
     */
    protected function handle(array $data): mixed
    {
        return $this->createPurchaseOrder($data);
    }

    /**
     * Create a new purchase order with line items and calculate totals.
     */
    public function createPurchaseOrder(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            [$subtotal, $taxAmount] = $this->calculateTotals($lines);

            $orderData = array_merge(
                array_diff_key($data, ['lines' => null]),
                [
                    'subtotal'     => $subtotal,
                    'tax_amount'   => $taxAmount,
                    'total_amount' => $subtotal + $taxAmount
                        + (float) ($data['shipping_amount'] ?? 0)
                        - (float) ($data['discount_amount'] ?? 0),
                    'balance_due'  => $subtotal + $taxAmount
                        + (float) ($data['shipping_amount'] ?? 0)
                        - (float) ($data['discount_amount'] ?? 0),
                ],
            );

            $order = $this->repository->create($orderData);

            foreach ($lines as $line) {
                $lineTotal = ((float) $line['quantity_ordered'] * (float) $line['unit_cost'])
                    * (1 - ((float) ($line['discount_percent'] ?? 0) / 100));

                DB::table('purchase_order_lines')->insert(array_merge($line, [
                    'id'                => \Illuminate\Support\Str::uuid(),
                    'tenant_id'         => $order->tenant_id,
                    'purchase_order_id' => $order->id,
                    'line_total'        => $lineTotal,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]));
            }

            $this->addEvent(new PurchaseOrderCreated((int) ($order->tenant_id ?? 0), $order->id));
            $this->dispatchEvents();

            return $order;
        });
    }

    /**
     * Record receipt of goods against a purchase order.
     * Each receipt item contains product_id, quantity_received, unit_cost.
     */
    public function receiveOrder(string $id, array $receipts): mixed
    {
        return DB::transaction(function () use ($id, $receipts) {
            $order = $this->repository->find($id);
            if (! $order) {
                throw new PurchaseOrderNotFoundException($id);
            }

            $allowedStatuses = ['sent', 'draft', 'partial_receipt'];
            if (! in_array($order->status, $allowedStatuses, true)) {
                throw new InvalidOrderStatusException($order->status, 'received');
            }

            foreach ($receipts as $receipt) {
                DB::table('purchase_order_lines')
                    ->where('purchase_order_id', $id)
                    ->where('product_id', $receipt['product_id'])
                    ->increment('quantity_received', (float) $receipt['quantity_received']);
            }

            // Determine new status
            $totalOrdered  = DB::table('purchase_order_lines')->where('purchase_order_id', $id)
                ->sum('quantity_ordered');
            $totalReceived = DB::table('purchase_order_lines')->where('purchase_order_id', $id)
                ->sum('quantity_received');

            $newStatus = $totalReceived >= $totalOrdered ? 'received' : 'partial_receipt';

            return $this->repository->update($id, [
                'status'        => $newStatus,
                'received_date' => $newStatus === 'received' ? now()->toDateString() : null,
            ]);
        });
    }

    /**
     * Cancel a purchase order.
     */
    public function cancelOrder(string $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $order = $this->repository->find($id);
            if (! $order) {
                throw new PurchaseOrderNotFoundException($id);
            }

            if (! in_array($order->status, ['draft', 'sent'], true)) {
                throw new InvalidOrderStatusException($order->status, 'cancelled');
            }

            return $this->repository->update($id, ['status' => 'cancelled']);
        });
    }

    /**
     * Calculate subtotal and tax from purchase order lines.
     *
     * @return array{float, float}
     */
    private function calculateTotals(array $lines): array
    {
        $subtotal  = 0.0;
        $taxAmount = 0.0;

        foreach ($lines as $line) {
            $qty      = (float) ($line['quantity_ordered'] ?? 0);
            $cost     = (float) ($line['unit_cost'] ?? 0);
            $discount = (float) ($line['discount_percent'] ?? 0);
            $taxRate  = (float) ($line['tax_rate'] ?? 0);

            $lineBase   = $qty * $cost * (1 - $discount / 100);
            $subtotal  += $lineBase;
            $taxAmount += $lineBase * ($taxRate / 100);
        }

        return [$subtotal, $taxAmount];
    }
}
