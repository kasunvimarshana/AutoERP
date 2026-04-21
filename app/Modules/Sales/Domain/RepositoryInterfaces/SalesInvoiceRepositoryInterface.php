<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Sales\Domain\Entities\SalesInvoice;

interface SalesInvoiceRepositoryInterface extends RepositoryInterface
{
    public function save(SalesInvoice $invoice): SalesInvoice;

    public function findByTenantAndInvoiceNumber(int $tenantId, string $invoiceNumber): ?SalesInvoice;

    public function find(int|string $id, array $columns = ['*']): ?SalesInvoice;
}
