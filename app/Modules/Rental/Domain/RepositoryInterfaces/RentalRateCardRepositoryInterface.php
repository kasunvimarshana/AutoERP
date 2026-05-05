<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalRateCard;

interface RentalRateCardRepositoryInterface
{
    public function save(RentalRateCard $rateCard): RentalRateCard;

    public function findById(int $tenantId, int $id): ?RentalRateCard;

    public function findByCode(int $tenantId, string $code): ?RentalRateCard;

    /** @return array{data: RentalRateCard[], total: int, per_page: int, current_page: int} */
    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;

    public function existsByCode(int $tenantId, string $code, ?int $excludeId = null): bool;
}
