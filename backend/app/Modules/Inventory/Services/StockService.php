<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\StockLedgerRepository;
use App\Modules\Inventory\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class StockService extends BaseService
{
    protected StockLedgerRepository $stockLedgerRepository;
    protected ProductRepository $productRepository;

    /**
     * StockService constructor.
     */
    public function __construct(
        StockLedgerRepository $stockLedgerRepository,
        ProductRepository $productRepository
    ) {
        $this->stockLedgerRepository = $stockLedgerRepository;
        $this->productRepository = $productRepository;
        parent::__construct($stockLedgerRepository);
    }

    /**
     * Add stock (receive goods)
     */
    public function addStock(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            // Validate product exists
            $product = $this->productRepository->findById($data['product_id']);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Ensure tenant_id is set
            if (empty($data['tenant_id']) && auth()->check()) {
                $data['tenant_id'] = auth()->user()->tenant_id;
            }

            $ledgerEntry = $this->stockLedgerRepository->createStockIn($data);

            return [
                'success' => true,
                'message' => 'Stock added successfully',
                'data' => $ledgerEntry,
                'new_balance' => $ledgerEntry->running_balance,
            ];
        });
    }

    /**
     * Remove stock (issue goods)
     */
    public function removeStock(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            // Validate product exists
            $product = $this->productRepository->findById($data['product_id']);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Check if sufficient stock is available
            $currentBalance = $this->stockLedgerRepository->getStockBalance(
                $data['product_id'],
                $data['product_variant_id'] ?? null,
                $data['branch_id']
            );

            if ($currentBalance < $data['quantity']) {
                throw new \Exception("Insufficient stock. Available: {$currentBalance}, Required: {$data['quantity']}");
            }

            // Ensure tenant_id is set
            if (empty($data['tenant_id']) && auth()->check()) {
                $data['tenant_id'] = auth()->user()->tenant_id;
            }

            $ledgerEntry = $this->stockLedgerRepository->createStockOut($data);

            return [
                'success' => true,
                'message' => 'Stock removed successfully',
                'data' => $ledgerEntry,
                'new_balance' => $ledgerEntry->running_balance,
            ];
        });
    }

    /**
     * Transfer stock between branches
     */
    public function transferStock(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            // Validate product exists
            $product = $this->productRepository->findById($data['product_id']);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Check if sufficient stock is available at source branch
            $currentBalance = $this->stockLedgerRepository->getStockBalance(
                $data['product_id'],
                $data['product_variant_id'] ?? null,
                $data['from_branch_id']
            );

            if ($currentBalance < $data['quantity']) {
                throw new \Exception("Insufficient stock at source branch. Available: {$currentBalance}, Required: {$data['quantity']}");
            }

            // Ensure tenant_id is set
            if (empty($data['tenant_id']) && auth()->check()) {
                $data['tenant_id'] = auth()->user()->tenant_id;
            }

            $transactions = $this->stockLedgerRepository->createTransfer($data);

            return [
                'success' => true,
                'message' => 'Stock transferred successfully',
                'data' => $transactions,
                'from_balance' => $transactions['out']->running_balance,
                'to_balance' => $transactions['in']->running_balance,
            ];
        });
    }

    /**
     * Adjust stock (reconciliation)
     */
    public function adjustStock(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            // Validate product exists
            $product = $this->productRepository->findById($data['product_id']);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Get current balance
            $currentBalance = $this->stockLedgerRepository->getStockBalance(
                $data['product_id'],
                $data['product_variant_id'] ?? null,
                $data['branch_id']
            );

            // Calculate adjustment quantity
            $targetBalance = $data['target_balance'] ?? 0;
            $adjustmentQty = $targetBalance - $currentBalance;

            // Ensure tenant_id is set
            if (empty($data['tenant_id']) && auth()->check()) {
                $data['tenant_id'] = auth()->user()->tenant_id;
            }

            $data['quantity'] = $adjustmentQty;
            $ledgerEntry = $this->stockLedgerRepository->createAdjustment($data);

            return [
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'data' => $ledgerEntry,
                'old_balance' => $currentBalance,
                'adjustment' => $adjustmentQty,
                'new_balance' => $ledgerEntry->running_balance,
            ];
        });
    }

    /**
     * Get stock balance
     */
    public function getStockBalance(
        int $productId,
        ?int $variantId = null,
        ?int $branchId = null
    ): array {
        $balance = $this->stockLedgerRepository->getStockBalance(
            $productId,
            $variantId,
            $branchId
        );

        return [
            'success' => true,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'branch_id' => $branchId,
            'balance' => $balance,
        ];
    }

    /**
     * Get available batches using FIFO or FEFO strategy
     */
    public function getAvailableBatches(
        int $productId,
        ?int $variantId = null,
        ?int $branchId = null,
        float $requiredQuantity = 0,
        string $strategy = 'FEFO'
    ): array {
        $batches = $this->stockLedgerRepository->getAvailableBatches(
            $productId,
            $variantId,
            $branchId,
            $requiredQuantity,
            $strategy
        );

        return [
            'success' => true,
            'strategy' => $strategy,
            'required_quantity' => $requiredQuantity,
            'batches' => $batches,
            'total_available' => array_sum(array_column($batches, 'available_quantity')),
        ];
    }

    /**
     * Pick stock using FIFO or FEFO strategy
     */
    public function pickStock(
        int $productId,
        float $quantity,
        int $branchId,
        ?int $variantId = null,
        string $strategy = 'FEFO',
        array $referenceData = []
    ): array {
        return $this->executeInTransaction(function () use (
            $productId,
            $quantity,
            $branchId,
            $variantId,
            $strategy,
            $referenceData
        ) {
            // Get available batches
            $batches = $this->stockLedgerRepository->getAvailableBatches(
                $productId,
                $variantId,
                $branchId,
                $quantity,
                $strategy
            );

            if (empty($batches)) {
                throw new \Exception('No available batches found');
            }

            $totalPicked = array_sum(array_column($batches, 'pick_quantity'));
            if ($totalPicked < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$totalPicked}, Required: {$quantity}");
            }

            // Create stock out transactions for each batch
            $transactions = [];
            foreach ($batches as $batch) {
                if ($batch['pick_quantity'] > 0) {
                    $stockOutData = [
                        'tenant_id' => auth()->user()->tenant_id ?? null,
                        'branch_id' => $branchId,
                        'product_id' => $productId,
                        'product_variant_id' => $variantId,
                        'batch_id' => $batch['batch_id'],
                        'quantity' => $batch['pick_quantity'],
                        'reference_type' => $referenceData['reference_type'] ?? 'picking',
                        'reference_id' => $referenceData['reference_id'] ?? null,
                        'notes' => $referenceData['notes'] ?? "Picked using {$strategy} strategy",
                    ];

                    $transactions[] = $this->stockLedgerRepository->createStockOut($stockOutData);
                }
            }

            return [
                'success' => true,
                'message' => 'Stock picked successfully',
                'strategy' => $strategy,
                'batches_used' => $batches,
                'transactions' => $transactions,
                'total_picked' => $totalPicked,
            ];
        });
    }

    /**
     * Get stock movement history
     */
    public function getMovementHistory(
        int $productId,
        ?int $variantId = null,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $history = $this->stockLedgerRepository->getMovementHistory(
            $productId,
            $variantId,
            $branchId,
            $startDate,
            $endDate
        );

        return [
            'success' => true,
            'data' => $history,
        ];
    }
}
