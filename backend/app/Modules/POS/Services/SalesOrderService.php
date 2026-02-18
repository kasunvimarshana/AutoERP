<?php

namespace App\Modules\POS\Services;

use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\POS\DTOs\SalesOrderDTO;
use App\Modules\POS\Enums\SalesOrderStatus;
use App\Modules\POS\Events\SalesOrderConfirmed;
use App\Modules\POS\Events\SalesOrderCreated;
use App\Modules\POS\Models\SalesOrder;
use App\Modules\POS\Models\SalesOrderItem;
use App\Modules\POS\Repositories\SalesOrderRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SalesOrderService extends BaseService
{
    public function __construct(
        protected SalesOrderRepository $repository,
        private readonly StockMovementService $stockMovementService
    ) {}

    public function create(SalesOrderDTO $dto): SalesOrder
    {
        return DB::transaction(function () use ($dto) {
            $salesOrder = $this->repository->create([
                ...$dto->toArray(),
                'tenant_id' => auth()->user()->tenant_id,
                'order_number' => $this->repository->generateOrderNumber(),
                'status' => SalesOrderStatus::DRAFT,
            ]);

            $this->createItems($salesOrder, $dto->items);
            $salesOrder->calculateTotals();
            $salesOrder->save();

            // Dispatch event for async processing
            event(new SalesOrderCreated($salesOrder));

            return $salesOrder->load(['items.product', 'customer']);
        });
    }

    public function update(int $id, SalesOrderDTO $dto): SalesOrder
    {
        return DB::transaction(function () use ($id, $dto) {
            $salesOrder = $this->repository->findOrFail($id);

            if (! $salesOrder->canEdit()) {
                throw new \Exception('Sales order cannot be edited in current status');
            }

            $salesOrder->update($dto->toArray());

            // Delete existing items and recreate
            $salesOrder->items()->delete();
            $this->createItems($salesOrder, $dto->items);
            $salesOrder->calculateTotals();
            $salesOrder->save();

            return $salesOrder->load(['items.product', 'customer']);
        });
    }

    public function confirm(int $id): SalesOrder
    {
        return DB::transaction(function () use ($id) {
            $salesOrder = $this->repository->findOrFail($id);

            if (! $salesOrder->status->canTransitionTo(SalesOrderStatus::CONFIRMED)) {
                throw new \Exception('Cannot confirm sales order in current status');
            }

            // Reserve stock for the order
            foreach ($salesOrder->items as $item) {
                $this->stockMovementService->recordMovement([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $salesOrder->warehouse_id,
                    'quantity' => -$item->quantity,
                    'movement_type' => 'sale',
                    'reference_type' => 'sales_order',
                    'reference_id' => $salesOrder->id,
                    'notes' => "Reserved for sales order {$salesOrder->order_number}",
                ]);
            }

            $salesOrder->update(['status' => SalesOrderStatus::CONFIRMED]);

            // Dispatch event for async processing
            event(new SalesOrderConfirmed($salesOrder));

            return $salesOrder->load(['items.product', 'customer']);
        });
    }

    public function cancel(int $id): SalesOrder
    {
        return DB::transaction(function () use ($id) {
            $salesOrder = $this->repository->findOrFail($id);

            if (! $salesOrder->canCancel()) {
                throw new \Exception('Cannot cancel sales order in current status');
            }

            // If stock was reserved, return it
            if ($salesOrder->status === SalesOrderStatus::CONFIRMED) {
                foreach ($salesOrder->items as $item) {
                    $this->stockMovementService->recordMovement([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $salesOrder->warehouse_id,
                        'quantity' => $item->quantity,
                        'movement_type' => 'adjustment',
                        'reference_type' => 'sales_order_cancellation',
                        'reference_id' => $salesOrder->id,
                        'notes' => "Stock returned from cancelled order {$salesOrder->order_number}",
                    ]);
                }
            }

            $salesOrder->update(['status' => SalesOrderStatus::CANCELLED]);

            return $salesOrder->load(['items.product', 'customer']);
        });
    }

    public function findByCustomer(int $customerId, array $filters = []): Collection
    {
        return $this->repository->findByCustomer($customerId, $filters);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->repository->findByStatus($status);
    }

    public function search(string $search): Collection
    {
        return $this->repository->searchByCustomerOrOrderNumber($search);
    }

    private function createItems(SalesOrder $salesOrder, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $item = new SalesOrderItem([
                'product_id' => $itemData['product_id'],
                'line_number' => $index + 1,
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'] ?? 'pcs',
                'unit_price' => $itemData['unit_price'],
                'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                'tax_percentage' => $itemData['tax_percentage'] ?? 0,
                'description' => $itemData['description'] ?? null,
            ]);

            $item->calculateLineTotal();
            $salesOrder->items()->save($item);
        }
    }
}
