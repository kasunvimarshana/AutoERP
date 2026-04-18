<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\Exceptions\ProductCategoryNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class UpdateProductCategoryService extends BaseService implements UpdateProductCategoryServiceInterface
{
    public function __construct(private readonly ProductCategoryRepositoryInterface $productCategoryRepository)
    {
        parent::__construct($productCategoryRepository);
    }

    protected function handle(array $data): ProductCategory
    {
        $id = (int) ($data['id'] ?? 0);
        $productCategory = $this->productCategoryRepository->find($id);

        if (! $productCategory) {
            throw new ProductCategoryNotFoundException($id);
        }

        $dto = ProductCategoryData::fromArray($data);

        $productCategory->update(
            name: $dto->name,
            slug: $dto->slug,
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
