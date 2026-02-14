<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Support\Facades\DB;

class StockLedgerRepository extends BaseRepository
{
    /**
     * StockLedgerRepository constructor.
     */
    public function __construct(StockLedger $model)
    {
        parent::__construct($model);
    }

    /**
     * Create stock in transaction
     */
    public function createStockIn(array $data): StockLedger
    {
        $data['transaction_type'] = StockLedger::TYPE_IN;
        $data['transaction_date'] = $data['transaction_date'] ?? now();
        $data['user_id'] = $data['user_id'] ?? auth()->id();

        // Calculate running balance
        $data['running_balance'] = $this->calculateRunningBalance(
            $data['product_id'],
            $data['product_variant_id'] ?? null,
            $data['branch_id'],
            $data['quantity']
        );

        return $this->create($data);
    }

    /**
     * Create stock out transaction
     */
    public function createStockOut(array $data): StockLedger
    {
        $data['transaction_type'] = StockLedger::TYPE_OUT;
        $data['transaction_date'] = $data['transaction_date'] ?? now();
        $data['user_id'] = $data['user_id'] ?? auth()->id();
        
        // Make quantity negative for OUT transactions
        $data['quantity'] = -abs($data['quantity']);

        // Calculate running balance
        $data['running_balance'] = $this->calculateRunningBalance(
            $data['product_id'],
            $data['product_variant_id'] ?? null,
            $data['branch_id'],
            $data['quantity']
        );

        return $this->create($data);
    }

    /**
     * Create stock transfer transaction
     */
    public function createTransfer(array $data): array
    {
        // Create OUT transaction from source branch
        $outData = [
            'tenant_id' => $data['tenant_id'],
            'branch_id' => $data['from_branch_id'],
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'batch_id' => $data['batch_id'] ?? null,
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'] ?? 0,
            'total_cost' => ($data['unit_cost'] ?? 0) * $data['quantity'],
            'transaction_type' => StockLedger::TYPE_TRANSFER,
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference_type' => $data['reference_type'] ?? 'transfer',
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? 'Transfer out',
            'user_id' => $data['user_id'] ?? auth()->id(),
        ];

        // Create IN transaction to destination branch
        $inData = [
            'tenant_id' => $data['tenant_id'],
            'branch_id' => $data['to_branch_id'],
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'batch_id' => $data['batch_id'] ?? null,
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'] ?? 0,
            'total_cost' => ($data['unit_cost'] ?? 0) * $data['quantity'],
            'transaction_type' => StockLedger::TYPE_TRANSFER,
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference_type' => $data['reference_type'] ?? 'transfer',
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? 'Transfer in',
            'user_id' => $data['user_id'] ?? auth()->id(),
        ];

        $outTransaction = $this->createStockOut($outData);
        $inTransaction = $this->createStockIn($inData);

        return [
            'out' => $outTransaction,
            'in' => $inTransaction,
        ];
    }

    /**
     * Create stock adjustment transaction
     */
    public function createAdjustment(array $data): StockLedger
    {
        $data['transaction_type'] = StockLedger::TYPE_ADJUSTMENT;
        $data['transaction_date'] = $data['transaction_date'] ?? now();
        $data['user_id'] = $data['user_id'] ?? auth()->id();

        // Calculate running balance
        $data['running_balance'] = $this->calculateRunningBalance(
            $data['product_id'],
            $data['product_variant_id'] ?? null,
            $data['branch_id'],
            $data['quantity']
        );

        return $this->create($data);
    }

    /**
     * Calculate running balance for a product/variant at a branch
     */
    protected function calculateRunningBalance(
        int $productId,
        ?int $variantId,
        int $branchId,
        float $quantity
    ): float {
        $query = $this->model
            ->where('product_id', $productId)
            ->where('branch_id', $branchId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        $currentBalance = $query->sum('quantity');

        return $currentBalance + $quantity;
    }

    /**
     * Get stock balance for a product/variant at a branch
     */
    public function getStockBalance(
        int $productId,
        ?int $variantId = null,
        ?int $branchId = null
    ): float {
        $query = $this->model->where('product_id', $productId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Get stock balance by batch (for FIFO/FEFO)
     */
    public function getStockBalanceByBatch(
        int $productId,
        ?int $variantId = null,
        ?int $branchId = null,
        string $orderBy = 'expiry_date' // 'expiry_date' for FEFO, 'created_at' for FIFO
    ) {
        $query = DB::table('stock_ledger as sl')
            ->join('batches as b', 'sl.batch_id', '=', 'b.id')
            ->select([
                'b.id as batch_id',
                'b.batch_number',
                'b.expiry_date',
                'b.created_at',
                DB::raw('SUM(sl.quantity) as balance')
            ])
            ->where('sl.product_id', $productId)
            ->groupBy('b.id', 'b.batch_number', 'b.expiry_date', 'b.created_at')
            ->having('balance', '>', 0);

        if ($variantId) {
            $query->where('sl.product_variant_id', $variantId);
        }

        if ($branchId) {
            $query->where('sl.branch_id', $branchId);
        }

        // Order by expiry date (FEFO) or creation date (FIFO)
        $query->orderBy("b.{$orderBy}");

        return $query->get();
    }

    /**
     * Get available batches for picking (FIFO/FEFO)
     */
    public function getAvailableBatches(
        int $productId,
        ?int $variantId = null,
        ?int $branchId = null,
        float $requiredQuantity = 0,
        string $strategy = 'FEFO' // 'FEFO' or 'FIFO'
    ): array {
        $orderBy = $strategy === 'FEFO' ? 'expiry_date' : 'created_at';
        
        $batches = $this->getStockBalanceByBatch(
            $productId,
            $variantId,
            $branchId,
            $orderBy
        );

        $result = [];
        $remainingQty = $requiredQuantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0 && $requiredQuantity > 0) {
                break;
            }

            $availableQty = $batch->balance;
            $pickQty = $requiredQuantity > 0 ? min($remainingQty, $availableQty) : $availableQty;

            $result[] = [
                'batch_id' => $batch->batch_id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
                'available_quantity' => $availableQty,
                'pick_quantity' => $pickQty,
            ];

            $remainingQty -= $pickQty;
        }

        return $result;
    }

    /**
     * Get stock movement history
     */
    public function getMovementHistory(
        int $productId,
        ?int $variantId = null,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 50
    ) {
        $query = $this->model
            ->where('product_id', $productId)
            ->with(['user', 'branch', 'batch']);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
