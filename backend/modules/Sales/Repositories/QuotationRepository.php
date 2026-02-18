<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Models\Quotation;

class QuotationRepository extends BaseRepository
{
    protected function model(): string
    {
        return Quotation::class;
    }

    /**
     * Get quotations with filters and pagination.
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['customer', 'items']);

        // Filter by customer
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->where('quote_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('quote_date', '<=', $filters['date_to']);
        }

        // Search by quote number
        if (! empty($filters['search'])) {
            $query->where('quote_number', 'like', "%{$filters['search']}%");
        }

        // Filter valid/expired
        if (isset($filters['valid_only']) && $filters['valid_only']) {
            $query->valid();
        }

        if (isset($filters['expired_only']) && $filters['expired_only']) {
            $query->expired();
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'quote_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find quotation by quote number.
     */
    public function findByQuoteNumber(string $quoteNumber): ?Quotation
    {
        return $this->model->where('quote_number', $quoteNumber)->first();
    }

    /**
     * Get quotations pending conversion.
     */
    public function getPendingConversion(): array
    {
        return $this->model
            ->where('status', QuotationStatus::ACCEPTED)
            ->whereNull('converted_to_order_id')
            ->valid()
            ->get()
            ->toArray();
    }

    /**
     * Generate next quote number.
     */
    public function generateNextQuoteNumber(string $prefix = 'QT'): string
    {
        $year = date('Y');
        $month = date('m');
        $pattern = "{$prefix}-{$year}{$month}-%";

        $lastQuote = $this->model
            ->where('quote_number', 'like', $pattern)
            ->orderByDesc('quote_number')
            ->first();

        if (! $lastQuote) {
            return "{$prefix}-{$year}{$month}-0001";
        }

        $lastNumber = (int) substr($lastQuote->quote_number, -4);
        $nextNumber = $lastNumber + 1;

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }

    /**
     * Get expired quotations that need status update.
     */
    public function getExpiredQuotations(): array
    {
        return $this->model
            ->whereIn('status', [QuotationStatus::DRAFT, QuotationStatus::SENT])
            ->where('valid_until', '<', now())
            ->whereNotNull('valid_until')
            ->get()
            ->toArray();
    }

    /**
     * Mark quotation as converted to order.
     */
    public function markAsConverted(int $quotationId, int $orderId): bool
    {
        return $this->model
            ->where('id', $quotationId)
            ->update([
                'status' => QuotationStatus::CONVERTED,
                'converted_to_order_id' => $orderId,
                'converted_at' => now(),
            ]);
    }
}
