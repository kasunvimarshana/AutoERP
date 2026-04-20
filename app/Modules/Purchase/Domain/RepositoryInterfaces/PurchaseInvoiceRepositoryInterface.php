<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;

interface PurchaseInvoiceRepositoryInterface extends RepositoryInterface
{
    public function save(PurchaseInvoice $invoice): PurchaseInvoice;

    public function find(int|string $id, array $columns = ['*']): ?PurchaseInvoice;
}
