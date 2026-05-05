<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\RepositoryInterfaces;

use Modules\ReturnRefund\Domain\Entities\ReturnRefund;
use Modules\ReturnRefund\Domain\ValueObjects\ReturnStatus;

interface ReturnRefundRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?ReturnRefund;

    /** @return ReturnRefund[] */
    public function findByTenant(int $tenantId, array $filters = []): array;

    /** @return ReturnRefund[] */
    public function findByRental(int $rentalId, int $tenantId): array;

    public function save(ReturnRefund $returnRefund): ReturnRefund;

    public function updateStatus(int $id, int $tenantId, ReturnStatus $status): void;

    public function delete(int $id, int $tenantId): void;
}
