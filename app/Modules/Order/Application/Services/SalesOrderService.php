<?php

declare(strict_types=1);

namespace Modules\Order\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Order\Application\Contracts\SalesOrderServiceInterface;
use Modules\Order\Domain\Contracts\Repositories\SalesOrderRepositoryInterface;
use Modules\Order\Domain\Events\SalesOrderCreated;
use Modules\Order\Domain\Exceptions\InvalidOrderStatusException;
use Modules\Order\Domain\Exceptions\SalesOrderNotFoundException;

class SalesOrderService extends BaseService implements SalesOrderServiceInterface
{
    public function __construct(SalesOrderRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler.
     */
    protected function handle(array $data): mixed
    {
        return $this->createSalesOrder($data);
    }

    /**
     * Create a new sales order with line items and calculate totals.
     */
    public function createSalesOrder(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            [$subtotal, $taxAmount] = $this->calculateTotals($lines);

            $orderData = array_merge(
                array_diff_key($data, ['lines' => null]),
                [
                    'subtotal'        => $subtotal,
                    'tax_amount'      => $taxAmount,
                    'total_amount'    => $subtotal + $taxAmount
                        + (float) ($data['shipping_amount'] ?? 0)
                        - (float) ($data['discount_amount'] ?? 0),
                    'balance_due'     => $subtotal + $taxAmount
                        + (float) ($data['shipping_amount'] ?? 0)
                        - (float) ($data['discount_amount'] ?? 0),
                ],
            );

            $order = $this->repository->create($orderData);

            // Create lines
            foreach ($lines as $line) {
                $lineTotal = ((float) $line['quantity_ordered'] * (float) $line['unit_price'])
                    * (1 - ((float) ($line['discount_percent'] ?? 0) / 100));

                DB::table('sales_order_lines')->insert(array_merge($line, [
                    'id'             => \Illuminate\Support\Str::uuid(),
                    'tenant_id'      => $order->tenant_id,
                    'sales_order_id' => $order->id,
                    'line_total'     => $lineTotal,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]));
            }

            $this->addEvent(new SalesOrderCreated((int) ($order->tenant_id ?? 0), $order->id));
            $this->dispatchEvents();

            return $order;
        });
    }

    /**
     * Confirm a draft sales order.
     */
    public function confirmOrder(string $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $order = $this->repository->find($id);
            if (! $order) {
                throw new SalesOrderNotFoundException($id);
            }

            if ($order->status !== 'draft') {
                throw new InvalidOrderStatusException($order->status, 'confirmed');
            }

            return $this->repository->update($id, ['status' => 'confirmed']);
        });
    }

    /**
     * Cancel a sales order.
     */
    public function cancelOrder(string $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $order = $this->repository->find($id);
            if (! $order) {
                throw new SalesOrderNotFoundException($id);
            }

            $cancellableStatuses = ['draft', 'confirmed'];
            if (! in_array($order->status, $cancellableStatuses, true)) {
                throw new InvalidOrderStatusException($order->status, 'cancelled');
            }

            return $this->repository->update($id, ['status' => 'cancelled']);
        });
    }

    /**
     * Calculate subtotal and tax totals from order lines.
     *
     * @return array{float, float}
     */
    private function calculateTotals(array $lines): array
    {
        $subtotal  = 0.0;
        $taxAmount = 0.0;

        foreach ($lines as $line) {
            $qty       = (float) ($line['quantity_ordered'] ?? 0);
            $price     = (float) ($line['unit_price'] ?? 0);
            $discount  = (float) ($line['discount_percent'] ?? 0);
            $taxRate   = (float) ($line['tax_rate'] ?? 0);

            $lineBase   = $qty * $price * (1 - $discount / 100);
            $subtotal  += $lineBase;
            $taxAmount += $lineBase * ($taxRate / 100);
        }

        return [$subtotal, $taxAmount];
    }
}
