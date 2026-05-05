<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\RepositoryInterfaces;

use Modules\ReturnRefund\Domain\Entities\RefundTransaction;

interface RefundTransactionRepositoryInterface
{
    public function create(RefundTransaction $refund): void;

    public function findById(string $id): ?RefundTransaction;

    public function findByRefundNumber(string $tenantId, string $refundNumber): ?RefundTransaction;

    public function getByStatus(string $tenantId, string $status, int $page = 1, int $limit = 50): array;

    public function update(RefundTransaction $refund): void;
}
