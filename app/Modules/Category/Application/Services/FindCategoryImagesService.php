<?php

declare(strict_types=1);

namespace Modules\Category\Application\Services;

use Modules\Category\Application\Contracts\FindCategoryImagesServiceInterface;
use Modules\Category\Domain\Entities\CategoryImage;
use Modules\Category\Domain\RepositoryInterfaces\CategoryImageRepositoryInterface;

/**
 * Delegates read queries for category images to the repository.
 *
 * Keeping query logic here (rather than in the controller) upholds DIP:
 * controllers depend on this service interface, not on the repository.
 */
class FindCategoryImagesService implements FindCategoryImagesServiceInterface
{
    public function __construct(
        private readonly CategoryImageRepositoryInterface $imageRepository
    ) {}

    public function findByUuid(string $uuid): ?CategoryImage
    {
        return $this->imageRepository->findByUuid($uuid);
    }

    public function findByCategory(int $categoryId): ?CategoryImage
    {
        return $this->imageRepository->findByCategory($categoryId);
    }
}
