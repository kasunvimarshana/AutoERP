<?php

declare(strict_types=1);

namespace Modules\Receipts\Domain\RepositoryInterfaces;

use Modules\Receipts\Domain\Entities\Receipt;

interface ReceiptRepositoryInterface
{
    public function findById(string $id): ?Receipt;

    /** @return Receipt[] */
    public function findByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Receipt[] */
    public function findByPayment(string $tenantId, string $paymentId): array;

    public function save(Receipt $receipt): Receipt;

    public function updateStatus(string $id, string $status): Receipt;

    public function delete(string $id): void;
}
