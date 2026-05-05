<?php declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalTransaction;

interface ManageRentalTransactionServiceInterface
{
    public function checkOut(array $data): RentalTransaction;

    public function checkIn(int $tenantId, string $transactionId, array $data): RentalTransaction;

    public function getOpen(int $tenantId): array;
}
