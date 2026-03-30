<?php

declare(strict_types=1);

namespace Modules\Category\Application\Contracts;

use Modules\Category\Domain\Entities\CategoryImage;

/**
 * Contract for querying category image records.
 *
 * Exposes read operations through the service layer to avoid direct
 * repository injection in controllers (DIP compliance).
 */
interface FindCategoryImagesServiceInterface
{
    public function findByUuid(string $uuid): ?CategoryImage;

    public function findByCategory(int $categoryId): ?CategoryImage;
}
