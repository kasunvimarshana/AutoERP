<?php

namespace App\Modules\POS\Services;

use App\Modules\POS\DTOs\QuotationDTO;
use App\Modules\POS\DTOs\SalesOrderDTO;
use App\Modules\POS\Events\QuotationCreated;
use App\Modules\POS\Models\Quotation;
use App\Modules\POS\Models\QuotationItem;
use App\Modules\POS\Repositories\QuotationRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QuotationService extends BaseService
{
    public function __construct(
        protected QuotationRepository $repository,
        private readonly SalesOrderService $salesOrderService
    ) {}

    public function create(QuotationDTO $dto): Quotation
    {
        return DB::transaction(function () use ($dto) {
            $quotation = $this->repository->create([
                ...$dto->toArray(),
                'tenant_id' => auth()->user()->tenant_id,
                'quotation_number' => $this->repository->generateQuotationNumber(),
                'status' => 'draft',
            ]);

            $this->createItems($quotation, $dto->items);
            $quotation->calculateTotals();
            $quotation->save();

            // Dispatch event for async processing
            event(new QuotationCreated($quotation));

            return $quotation->load(['items.product', 'customer']);
        });
    }

    public function update(int $id, QuotationDTO $dto): Quotation
    {
        return DB::transaction(function () use ($id, $dto) {
            $quotation = $this->repository->findOrFail($id);

            if ($quotation->isConverted()) {
                throw new \Exception('Cannot edit converted quotation');
            }

            $quotation->update($dto->toArray());

            // Delete existing items and recreate
            $quotation->items()->delete();
            $this->createItems($quotation, $dto->items);
            $quotation->calculateTotals();
            $quotation->save();

            return $quotation->load(['items.product', 'customer']);
        });
    }

    public function convertToSalesOrder(int $id, int $warehouseId): \App\Modules\POS\Models\SalesOrder
    {
        return DB::transaction(function () use ($id, $warehouseId) {
            $quotation = $this->repository->findOrFail($id);

            if ($quotation->isConverted()) {
                throw new \Exception('Quotation already converted to sales order');
            }

            if (! $quotation->isValid()) {
                throw new \Exception('Quotation has expired');
            }

            // Create sales order from quotation
            $salesOrderItems = $quotation->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'discount_percentage' => $item->discount_percentage,
                    'tax_percentage' => $item->tax_percentage,
                    'description' => $item->description,
                ];
            })->toArray();

            $salesOrderDTO = new SalesOrderDTO(
                customerId: $quotation->customer_id,
                warehouseId: $warehouseId,
                userId: $quotation->user_id,
                orderDate: now()->toDateString(),
                deliveryDate: null,
                items: $salesOrderItems,
                notes: $quotation->notes,
                termsAndConditions: $quotation->terms_and_conditions
            );

            $salesOrder = $this->salesOrderService->create($salesOrderDTO);

            // Update quotation
            $quotation->update([
                'converted_to_sales_order_id' => $salesOrder->id,
                'converted_at' => now(),
                'status' => 'converted',
            ]);

            return $salesOrder;
        });
    }

    public function findByCustomer(int $customerId, array $filters = []): Collection
    {
        return $this->repository->findByCustomer($customerId, $filters);
    }

    public function findExpiredQuotations(): Collection
    {
        return $this->repository->findExpiredQuotations();
    }

    private function createItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $item = new QuotationItem([
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
            $quotation->items()->save($item);
        }
    }
}
