<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalRateCard;

interface FindRentalRateCardServiceInterface
{
    public function findById(int $tenantId, int $id): RentalRateCard;

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;
}
