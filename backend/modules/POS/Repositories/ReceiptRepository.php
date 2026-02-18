<?php

declare(strict_types=1);

namespace Modules\POS\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\POS\Models\Receipt;
use Illuminate\Database\Eloquent\Collection;

/**
 * Receipt Repository
 * 
 * Handles data access for receipt records
 */
class ReceiptRepository extends BaseRepository
{
    public function __construct(Receipt $model)
    {
        parent::__construct($model);
    }

    /**
     * Get receipts for a transaction
     *
     * @param string $transactionId
     * @return Collection
     */
    public function getByTransaction(string $transactionId): Collection
    {
        return $this->model
            ->where('transaction_id', $transactionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get latest receipt for a transaction
     *
     * @param string $transactionId
     * @return Receipt|null
     */
    public function getLatestByTransaction(string $transactionId): ?Receipt
    {
        return $this->model
            ->where('transaction_id', $transactionId)
            ->latest('created_at')
            ->first();
    }

    /**
     * Mark receipt as printed
     *
     * @param string $receiptId
     * @return bool
     */
    public function markAsPrinted(string $receiptId): bool
    {
        return $this->model
            ->where('id', $receiptId)
            ->update([
                'printed_at' => now(),
                'print_count' => \DB::raw('print_count + 1')
            ]);
    }

    /**
     * Get receipts printed within date range
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return Collection
     */
    public function getPrintedBetween(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): Collection
    {
        return $this->model
            ->whereBetween('printed_at', [$startDate, $endDate])
            ->with('transaction')
            ->orderBy('printed_at', 'desc')
            ->get();
    }
}
