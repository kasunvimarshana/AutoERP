<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Application\Contracts;

use Modules\ReturnRefund\Application\DTOs\CreateReturnRefundDTO;
use Modules\ReturnRefund\Application\DTOs\UpdateReturnRefundDTO;
use Modules\ReturnRefund\Domain\Entities\ReturnRefund;
use Modules\ReturnRefund\Domain\ValueObjects\ReturnStatus;

interface ReturnRefundServiceInterface
{
    public function getById(int $id, int $tenantId): ReturnRefund;

    /** @return ReturnRefund[] */
    public function listByTenant(int $tenantId, array $filters = []): array;

    /** @return ReturnRefund[] */
    public function listByRental(int $rentalId, int $tenantId): array;

    public function create(CreateReturnRefundDTO $dto): ReturnRefund;

    public function update(int $id, int $tenantId, UpdateReturnRefundDTO $dto): ReturnRefund;

    public function changeStatus(int $id, int $tenantId, ReturnStatus $status): ReturnRefund;

    public function delete(int $id, int $tenantId): void;
}
