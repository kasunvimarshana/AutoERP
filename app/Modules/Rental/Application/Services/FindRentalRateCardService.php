<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Rental\Application\Contracts\FindRentalRateCardServiceInterface;
use Modules\Rental\Domain\Entities\RentalRateCard;
use Modules\Rental\Domain\Exceptions\AssetNotFoundException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRateCardRepositoryInterface;

class FindRentalRateCardService implements FindRentalRateCardServiceInterface
{
    public function __construct(
        private readonly RentalRateCardRepositoryInterface $rateCardRepository,
    ) {}

    public function findById(int $tenantId, int $id): RentalRateCard
    {
        $rateCard = $this->rateCardRepository->findById($tenantId, $id);
        if ($rateCard === null) {
            throw new AssetNotFoundException($id);
        }

        return $rateCard;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        return $this->rateCardRepository->paginate($tenantId, $filters, $perPage, $page);
    }
}
