<?php

declare(strict_types=1);

namespace App\Modules\Sales\Repositories;

use App\Core\Repositories\BaseRepositoryInterface;

interface QuoteRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find quote by quote number.
     */
    public function findByQuoteNumber(string $quoteNumber);

    /**
     * Get quotes by customer.
     */
    public function getByCustomer(int $customerId, int $perPage = 15);

    /**
     * Get quotes by status.
     */
    public function getByStatus(string $status, int $perPage = 15);

    /**
     * Get expired quotes.
     */
    public function getExpiredQuotes(int $perPage = 15);
}
