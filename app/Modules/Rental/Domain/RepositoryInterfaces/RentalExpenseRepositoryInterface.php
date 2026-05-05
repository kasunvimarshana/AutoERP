<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalExpense;

interface RentalExpenseRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?RentalExpense;

    /** @return RentalExpense[] */
    public function findByTenant(int $tenantId, array $filters = []): array;

    /** @return RentalExpense[] */
    public function findByBooking(int $tenantId, int $bookingId): array;

    public function save(RentalExpense $expense): RentalExpense;

    public function delete(int $tenantId, int $id): bool;
}
