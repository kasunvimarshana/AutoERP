<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalTransaction;

interface RentalTransactionRepositoryInterface
{
    public function create(RentalTransaction $transaction): void;
    public function findById(string $id): ?RentalTransaction;
    public function findOpenByAgreementId(string $agreementId): ?RentalTransaction;
    public function getOpenByTenant(string $tenantId, int $page = 1, int $limit = 50): array;
    public function update(RentalTransaction $transaction): void;
}
