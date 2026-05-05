<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\RepositoryInterfaces;

use Modules\ReturnRefund\Domain\Entities\ReturnInspection;

interface ReturnInspectionRepositoryInterface
{
    public function create(ReturnInspection $inspection): void;

    public function findById(string $id): ?ReturnInspection;

    public function findByRentalTransactionId(string $rentalTransactionId): ?ReturnInspection;

    public function update(ReturnInspection $inspection): void;
}
