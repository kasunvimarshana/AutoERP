<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\Exceptions\ProductCategoryNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class UpdateProductCategoryService extends BaseService implements UpdateProductCategoryServiceInterface
{
    public function __construct(
        private readonly ProductCategoryRepositoryInterface $productCategoryRepository,
        private readonly SlugGeneratorInterface $slugGenerator,
    ) {
        parent::__construct($productCategoryRepository);
    }

    protected function handle(array $data): ProductCategory
    {
        $id = (int) ($data['id'] ?? 0);
        $productCategory = $this->productCategoryRepository->find($id);

        if (! $productCategory) {
            throw new ProductCategoryNotFoundException($id);
        }

        $data['slug'] = $this->slugGenerator->generate(
            preferredValue: isset($data['slug']) ? (string) $data['slug'] : null,
            sourceValue: isset($data['name']) ? (string) $data['name'] : $productCategory->getName(),
            fallback: $productCategory->getSlug(),
        );

        $dto = ProductCategoryData::fromArray($data);

        $productCategory->update(
            name: $dto->name,
            slug: $dto->slug,
            imagePath: $dto->image_path,
            parentId: $dto->parent_id,
            code: $dto->code,
            path: $dto->path,
            depth: $dto->depth,
            isActive: $dto->is_active,
            description: $dto->description,
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        return $this->productCategoryRepository->save($productCategory);
    }
}
