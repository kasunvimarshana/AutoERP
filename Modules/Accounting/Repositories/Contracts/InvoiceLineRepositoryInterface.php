<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Invoice Line Repository Interface
 *
 * Defines the contract for invoice line data access operations.
 */
interface InvoiceLineRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get lines by invoice.
     */
    public function getByInvoice(int $invoiceId): Collection;

    /**
     * Get lines by product.
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Get lines by account.
     */
    public function getByAccount(int $accountId): Collection;
}
