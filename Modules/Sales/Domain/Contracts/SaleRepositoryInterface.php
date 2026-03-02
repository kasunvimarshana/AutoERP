<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Contracts;

use Modules\Sales\Domain\Entities\Sale;

interface SaleRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Sale;

    public function findByInvoiceNumber(string $invoiceNumber, int $tenantId): ?Sale;

    /** @return Sale[] */
    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function save(Sale $sale): Sale;

    public function delete(int $id, int $tenantId): void;

    public function generateInvoiceNumber(int $tenantId, int $organisationId): string;
}

