<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Purchase\Domain\Entities\PurchaseInvoiceLine;

interface PurchaseInvoiceLineRepositoryInterface extends RepositoryInterface
{
    public function save(PurchaseInvoiceLine $line): PurchaseInvoiceLine;

    public function find(int|string $id, array $columns = ['*']): ?PurchaseInvoiceLine;

    /** @return PurchaseInvoiceLine[] */
    public function findByInvoiceId(int $invoiceId): array;
}
