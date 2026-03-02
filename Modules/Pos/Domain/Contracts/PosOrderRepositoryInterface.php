<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Contracts;

use Modules\Pos\Domain\Entities\PosOrder;
use Modules\Pos\Domain\Entities\PosPayment;

interface PosOrderRepositoryInterface
{
    public function save(PosOrder $order): PosOrder;

    public function findById(int $id, int $tenantId): ?PosOrder;

    public function findAll(int $tenantId, int $page, int $perPage): array;

    public function findBySession(int $sessionId, int $tenantId): array;

    public function saveLines(int $orderId, array $lines): array;

    public function findLines(int $orderId, int $tenantId): array;

    public function savePayment(PosPayment $payment): PosPayment;

    public function findPayments(int $orderId, int $tenantId): array;

    public function delete(int $id, int $tenantId): void;
}
