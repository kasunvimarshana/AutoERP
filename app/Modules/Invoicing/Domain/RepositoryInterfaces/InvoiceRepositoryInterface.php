<?php

declare(strict_types=1);

namespace Modules\Invoicing\Domain\RepositoryInterfaces;

use Modules\Invoicing\Domain\Entities\Invoice;

interface InvoiceRepositoryInterface
{
    public function findById(string $id): ?Invoice;

    /** @return Invoice[] */
    public function findByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Invoice[] */
    public function findByEntity(string $tenantId, string $entityType, string $entityId): array;

    public function save(Invoice $invoice): Invoice;

    public function updateStatus(string $id, string $status): Invoice;

    public function recordPayment(string $id, string $amount): Invoice;

    public function delete(string $id): void;
}
