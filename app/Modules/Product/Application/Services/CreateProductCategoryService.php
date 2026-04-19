<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class CreateProductCategoryService extends BaseService implements CreateProductCategoryServiceInterface
{
    public function __construct(private readonly ProductCategoryRepositoryInterface $productCategoryRepository)
    {
        parent::__construct($productCategoryRepository);
    }

    protected function handle(array $data): ProductCategory
    {
        $dto = ProductCategoryData::fromArray($data);

        $productCategory = new ProductCategory(
            tenantId: $dto->tenant_id,
            parentId: $dto->parent_id,
            name: $dto->name,
            slug: $dto->slug,
            imagePath: $dto->image_path,
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
