<?php

declare(strict_types=1);

namespace Modules\Invoicing\Application\Contracts;

use Modules\Invoicing\Application\DTOs\CreateInvoiceDTO;
use Modules\Invoicing\Application\DTOs\RecordInvoicePaymentDTO;
use Modules\Invoicing\Domain\Entities\Invoice;

interface InvoiceServiceInterface
{
    public function getById(string $id): Invoice;

    /** @return Invoice[] */
    public function listByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Invoice[] */
    public function listByEntity(string $tenantId, string $entityType, string $entityId): array;

    public function create(CreateInvoiceDTO $dto): Invoice;

    public function updateStatus(string $id, string $status): Invoice;

    public function recordPayment(RecordInvoicePaymentDTO $dto): Invoice;

    public function delete(string $id): void;
}
