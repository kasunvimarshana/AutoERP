<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Helpers\DecimalHelper;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Inventory\Application\DTOs\StockTransactionDTO;
use Modules\Inventory\Domain\Contracts\InventoryServiceContract;
use Modules\Sales\Application\DTOs\CreateSalesOrderDTO;
use Modules\Sales\Domain\Contracts\SalesRepositoryContract;
use Modules\Sales\Domain\Entities\SalesDelivery;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Domain\Entities\SalesOrder;

/**
 * Sales service.
 *
 * Orchestrates all sales order use cases.
 * All arithmetic uses DecimalHelper (BCMath) — no float allowed.
 */
class SalesService implements ServiceContract
{
    public function __construct(
        private readonly SalesRepositoryContract $salesRepository,
        private readonly ?InventoryServiceContract $inventoryService = null,
    ) {}

    /**
     * Create a new sales order with order lines.
     *
     * Calculates line totals, subtotal, tax, and grand total using BCMath.
     * Wrapped in a DB transaction for atomicity.
     */
    public function createOrder(CreateSalesOrderDTO $dto): SalesOrder
    {
        return DB::transaction(function () use ($dto): SalesOrder {
            $subtotal   = '0';
            $taxAmount  = '0';
            $lineData   = [];

            foreach ($dto->lines as $line) {
                $quantity        = (string) $line['quantity'];
                $unitPrice       = (string) $line['unit_price'];
                $discountAmount  = (string) $line['discount_amount'];
                $taxRate         = (string) $line['tax_rate'];

                // line_total = (quantity × unit_price) - discount_amount  [intermediate: 8dp]
                $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
                $lineTotal = DecimalHelper::sub($gross, $discountAmount, DecimalHelper::SCALE_STANDARD);

                // per-line tax contribution
                $lineTax = DecimalHelper::mul($lineTotal, $taxRate, DecimalHelper::SCALE_INTERMEDIATE);

                $subtotal  = DecimalHelper::add($subtotal, $lineTotal, DecimalHelper::SCALE_STANDARD);
                $taxAmount = DecimalHelper::add($taxAmount, $lineTax, DecimalHelper::SCALE_STANDARD);

                $lineData[] = [
                    'product_id'      => (int) $line['product_id'],
                    'uom_id'          => (int) $line['uom_id'],
                    'quantity'        => DecimalHelper::round($quantity, DecimalHelper::SCALE_STANDARD),
                    'unit_price'      => DecimalHelper::round($unitPrice, DecimalHelper::SCALE_STANDARD),
                    'discount_amount' => DecimalHelper::round($discountAmount, DecimalHelper::SCALE_STANDARD),
                    'tax_rate'        => DecimalHelper::round($taxRate, DecimalHelper::SCALE_STANDARD),
                    'line_total'      => DecimalHelper::round($lineTotal, DecimalHelper::SCALE_STANDARD),
                ];
            }

            // total_amount = subtotal + tax_amount (order-level discount is 0 at creation)
            $totalAmount = DecimalHelper::add($subtotal, $taxAmount, DecimalHelper::SCALE_STANDARD);

            /** @var SalesOrder $order */
            $order = $this->salesRepository->create([
                'customer_id'     => $dto->customerId,
                'order_number'    => $this->generateOrderNumber(),
                'status'          => 'quotation',
                'order_date'      => $dto->orderDate,
                'currency_code'   => $dto->currencyCode,
                'subtotal'        => DecimalHelper::round($subtotal, DecimalHelper::SCALE_STANDARD),
                'discount_amount' => '0.0000',
                'tax_amount'      => DecimalHelper::round($taxAmount, DecimalHelper::SCALE_STANDARD),
                'total_amount'    => DecimalHelper::round($totalAmount, DecimalHelper::SCALE_STANDARD),
                'notes'           => $dto->notes,
                'warehouse_id'    => $dto->warehouseId,
            ]);

            foreach ($lineData as $line) {
                $order->lines()->create(array_merge($line, ['tenant_id' => $order->tenant_id]));
            }

            return $order->load('lines');
        });
    }

    /**
     * Confirm a sales order (transition from quotation to confirmed).
     */
    public function confirmOrder(int $orderId): SalesOrder
    {
        return DB::transaction(function () use ($orderId): SalesOrder {
            /** @var SalesOrder $order */
            $order = $this->salesRepository->findOrFail($orderId);
            $order->update(['status' => 'confirmed']);

            return $order->fresh();
        });
    }

    /**
     * List sales orders with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function listOrders(array $filters = []): Collection
    {
        return $this->salesRepository->all();
    }

    /**
     * Show a single sales order by ID.
     */
    public function showOrder(int|string $id): SalesOrder
    {
        /** @var SalesOrder $order */
        $order = $this->salesRepository->findOrFail($id);
        return $order;
    }

    /**
     * Cancel a sales order.
     */
    public function cancelOrder(int|string $id): SalesOrder
    {
        return DB::transaction(function () use ($id): SalesOrder {
            /** @var SalesOrder $order */
            $order = $this->salesRepository->findOrFail($id);
            $order->update(['status' => 'cancelled']);
            return $order->fresh();
        });
    }

    /**
     * List customers.
     */
    public function listCustomers(): Collection
    {
        return $this->salesRepository->allCustomers();
    }

    /**
     * Create a delivery record for a confirmed sales order.
     *
     * When an InventoryServiceContract is available and the order has a
     * warehouse_id, stock is automatically deducted for every order line
     * using the FIFO strategy. The deduction is wrapped inside the same
     * DB::transaction() so the delivery row is rolled back if stock is
     * insufficient.
     *
     * @param array<string, mixed> $data
     */
    public function createDelivery(int $orderId, array $data): SalesDelivery
    {
        return DB::transaction(function () use ($orderId, $data): SalesDelivery {
            /** @var SalesOrder $order */
            $order = $this->salesRepository->findOrFail($orderId);

            /** @var SalesDelivery $delivery */
            $delivery = SalesDelivery::create([
                'tenant_id'       => $order->tenant_id,
                'sales_order_id'  => $order->id,
                'delivery_number' => $this->generateDeliveryNumber(),
                'status'          => 'pending',
                'shipped_at'      => $data['shipped_at'] ?? null,
                'delivered_at'    => $data['delivered_at'] ?? null,
            ]);

            // Automatic stock deduction: deduct each order line via FIFO.
            // Runs inside the same transaction so the delivery is rolled back
            // if the InventoryService throws (e.g. insufficient stock).
            if ($this->inventoryService !== null && $order->warehouse_id !== null) {
                $order->load('lines');
                foreach ($order->lines as $line) {
                    $this->inventoryService->deductByStrategy(
                        productId:   (int) $line->product_id,
                        warehouseId: (int) $order->warehouse_id,
                        uomId:       (int) $line->uom_id,
                        quantity:    (string) $line->quantity,
                        // unitCost for the ledger entry — the acquisition cost is tracked
                        // in the StockItem; pass '0.0000' to avoid conflating the selling
                        // price (unit_price) with the COGS cost.
                        unitCost:    '0.0000',
                        notes:       "Auto-deduction for delivery {$delivery->delivery_number}",
                    );
                }
            }

            return $delivery;
        });
    }

    /**
     * List deliveries for a sales order.
     */
    public function listDeliveries(int $orderId): Collection
    {
        return SalesDelivery::query()
            ->where('sales_order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Show a single delivery by ID.
     */
    public function showDelivery(int|string $id): SalesDelivery
    {
        /** @var SalesDelivery $delivery */
        $delivery = SalesDelivery::findOrFail($id);
        return $delivery;
    }

    /**
     * Create an invoice for a confirmed sales order.
     *
     * Total amount copied from the order's total_amount using BCMath for safety.
     *
     * @param array<string, mixed> $data
     */
    public function createInvoice(int $orderId, array $data): SalesInvoice
    {
        return DB::transaction(function () use ($orderId, $data): SalesInvoice {
            /** @var SalesOrder $order */
            $order = $this->salesRepository->findOrFail($orderId);

            $totalAmount = isset($data['total_amount'])
                ? DecimalHelper::round((string) $data['total_amount'], DecimalHelper::SCALE_STANDARD)
                : DecimalHelper::round((string) $order->total_amount, DecimalHelper::SCALE_STANDARD);

            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::create([
                'tenant_id'      => $order->tenant_id,
                'sales_order_id' => $order->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'status'         => 'draft',
                'issued_at'      => $data['issued_at'] ?? now()->toDateString(),
                'due_date'       => $data['due_date'] ?? null,
                'total_amount'   => $totalAmount,
                'paid_amount'    => '0.0000',
            ]);

            return $invoice;
        });
    }

    /**
     * List invoices for a sales order.
     */
    public function listInvoices(int $orderId): Collection
    {
        return SalesInvoice::query()
            ->where('sales_order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Show a single invoice by ID.
     */
    public function showInvoice(int|string $id): SalesInvoice
    {
        /** @var SalesInvoice $invoice */
        $invoice = SalesInvoice::findOrFail($id);
        return $invoice;
    }

    /**
     * Process a sales return: restore inventory quantities to the correct batch.
     *
     * Each element of $lines must contain:
     *   - product_id  (int)
     *   - warehouse_id (int)
     *   - uom_id      (int)
     *   - quantity    (string — positive amount to restore)
     *   - unit_cost   (string — cost price of returned goods)
     *   - batch_number (string|null — optional, for batch-specific restores)
     *   - lot_number   (string|null — optional)
     *   - notes        (string|null — optional)
     *
     * When an InventoryServiceContract is not available the method is a no-op
     * (returns an empty array) so callers in test environments remain unaffected.
     *
     * @param array<int, array<string, mixed>> $lines
     * @return array<int, array{batch_number: string|null, quantity_deducted: string, transaction_id: int}>
     */
    public function createReturn(int $orderId, array $lines): array
    {
        if ($this->inventoryService === null) {
            return [];
        }

        return DB::transaction(function () use ($lines): array {
            $results = [];

            foreach ($lines as $line) {
                $dto = StockTransactionDTO::fromArray([
                    'transaction_type' => 'return',
                    'warehouse_id'     => (int) $line['warehouse_id'],
                    'product_id'       => (int) $line['product_id'],
                    'uom_id'           => (int) $line['uom_id'],
                    'quantity'         => (string) $line['quantity'],
                    'unit_cost'        => (string) $line['unit_cost'],
                    'batch_number'     => $line['batch_number'] ?? null,
                    'lot_number'       => $line['lot_number'] ?? null,
                    'notes'            => $line['notes'] ?? null,
                ]);

                $transaction = $this->inventoryService->recordTransaction($dto);

                $results[] = [
                    'batch_number'      => $transaction->batch_number,
                    'quantity_returned' => $transaction->quantity,
                    'transaction_id'    => $transaction->id,
                ];
            }

            return $results;
        });
    }

    /**
     * Generate a unique order number: SO-{YYYYMMDD}-{microseconds}.
     */
    private function generateOrderNumber(): string
    {
        return 'SO-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid('', true), -6));
    }

    /**
     * Generate a unique delivery number: DEL-{YYYYMMDD}-{suffix}.
     */
    private function generateDeliveryNumber(): string
    {
        return 'DEL-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid('', true), -6));
    }

    /**
     * Generate a unique invoice number: INV-{YYYYMMDD}-{suffix}.
     */
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid('', true), -6));
    }
}
