<?php
namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\Events\ProductCategoryCreated;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class CreateProductCategoryService implements CreateProductCategoryServiceInterface
{
    public function __construct(private readonly ProductCategoryRepositoryInterface $repository) {}

    public function execute(ProductCategoryData $data): ProductCategory
    {
        $category = $this->repository->create($data->toArray());
        Event::dispatch(new ProductCategoryCreated($category->tenantId, $category->id));
        return $category;
    }
}
