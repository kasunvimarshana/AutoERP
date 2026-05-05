<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Contracts;

use Modules\Payments\Application\DTOs\CreatePaymentDTO;
use Modules\Payments\Domain\Entities\Payment;

interface PaymentServiceInterface
{
    public function getById(string $tenantId, string $id): Payment;

    /** @return Payment[] */
    public function listByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Payment[] */
    public function listByInvoice(string $tenantId, string $invoiceId): array;

    public function create(CreatePaymentDTO $dto): Payment;

    public function updateStatus(string $tenantId, string $id, string $status): Payment;

    public function delete(string $tenantId, string $id): void;
}
