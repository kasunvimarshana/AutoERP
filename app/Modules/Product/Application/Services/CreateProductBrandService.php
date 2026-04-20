<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductBrandServiceInterface;
use Modules\Product\Application\DTOs\ProductBrandData;
use Modules\Product\Domain\Entities\ProductBrand;
use Modules\Product\Domain\RepositoryInterfaces\ProductBrandRepositoryInterface;

class CreateProductBrandService extends BaseService implements CreateProductBrandServiceInterface
{
    public function __construct(
        private readonly ProductBrandRepositoryInterface $productBrandRepository,
        private readonly SlugGeneratorInterface $slugGenerator,
    ) {
        parent::__construct($productBrandRepository);
    }

    protected function handle(array $data): ProductBrand
    {
        $data['slug'] = $this->slugGenerator->generate(
            preferredValue: isset($data['slug']) ? (string) $data['slug'] : null,
            sourceValue: isset($data['name']) ? (string) $data['name'] : null,
            fallback: 'brand',
        );

        $dto = ProductBrandData::fromArray($data);

        $productBrand = new ProductBrand(
            tenantId: $dto->tenant_id,
            parentId: $dto->parent_id,
            name: $dto->name,
            slug: $dto->slug,
            imagePath: $dto->image_path,
            code: $dto->code,
            path: $dto->path,
            depth: $dto->depth,
            isActive: $dto->is_active,
            website: $dto->website,
            description: $dto->description,
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        return $this->productBrandRepository->save($productBrand);
    }
}
