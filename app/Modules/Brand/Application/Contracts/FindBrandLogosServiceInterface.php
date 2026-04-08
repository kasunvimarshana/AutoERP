<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Contracts;

use Modules\Brand\Domain\Entities\BrandLogo;

/**
 * Contract for querying brand logo records.
 *
 * Exposes read operations through the service layer to avoid direct
 * repository injection in controllers (DIP compliance).
 */
interface FindBrandLogosServiceInterface
{
    public function findByUuid(string $uuid): ?BrandLogo;

    public function findByBrand(int $brandId): ?BrandLogo;
}
