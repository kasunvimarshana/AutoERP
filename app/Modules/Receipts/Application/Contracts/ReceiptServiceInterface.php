<?php

declare(strict_types=1);

namespace Modules\Receipts\Application\Contracts;

use Modules\Receipts\Application\DTOs\CreateReceiptDTO;
use Modules\Receipts\Domain\Entities\Receipt;

interface ReceiptServiceInterface
{
    public function getById(string $id): Receipt;

    /** @return Receipt[] */
    public function listByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Receipt[] */
    public function listByPayment(string $tenantId, string $paymentId): array;

    public function create(CreateReceiptDTO $dto): Receipt;

    public function updateStatus(string $id, string $status): Receipt;

    public function delete(string $id): void;
}
