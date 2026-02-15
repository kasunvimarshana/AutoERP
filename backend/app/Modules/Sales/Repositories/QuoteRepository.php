<?php

declare(strict_types=1);

namespace App\Modules\Sales\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Sales\Models\Quote;

class QuoteRepository extends BaseRepository implements QuoteRepositoryInterface
{
    /**
     * QuoteRepository constructor.
     */
    public function __construct(Quote $model)
    {
        parent::__construct($model);
    }

    /**
     * Find quote by quote number.
     */
    public function findByQuoteNumber(string $quoteNumber)
    {
        return $this->model->where('quote_number', $quoteNumber)->first();
    }

    /**
     * Get quotes by customer.
     */
    public function getByCustomer(int $customerId, int $perPage = 15)
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->with(['items', 'customer', 'currency'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get quotes by status.
     */
    public function getByStatus(string $status, int $perPage = 15)
    {
        return $this->model
            ->where('status', $status)
            ->with(['items', 'customer', 'currency'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get expired quotes.
     */
    public function getExpiredQuotes(int $perPage = 15)
    {
        return $this->model
            ->where('valid_until', '<', now())
            ->where('status', '!=', 'converted')
            ->with(['items', 'customer', 'currency'])
            ->orderBy('valid_until', 'desc')
            ->paginate($perPage);
    }
}
