<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Services;

use Modules\Brand\Application\Contracts\FindBrandLogosServiceInterface;
use Modules\Brand\Domain\Entities\BrandLogo;
use Modules\Brand\Domain\RepositoryInterfaces\BrandLogoRepositoryInterface;

/**
 * Delegates read queries for brand logos to the repository.
 *
 * Keeping query logic here (rather than in the controller) upholds DIP:
 * controllers depend on this service interface, not on the repository.
 */
class FindBrandLogosService implements FindBrandLogosServiceInterface
{
    public function __construct(
        private readonly BrandLogoRepositoryInterface $logoRepository
    ) {}

    public function findByUuid(string $uuid): ?BrandLogo
    {
        return $this->logoRepository->findByUuid($uuid);
    }

    public function findByBrand(int $brandId): ?BrandLogo
    {
        return $this->logoRepository->findByBrand($brandId);
    }
}
