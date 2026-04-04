<?php
namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class CreateProductService implements CreateProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $repository) {}

    public function execute(ProductData $data): Product
    {
        $product = $this->repository->create($data->toArray());
        Event::dispatch(new ProductCreated($product->tenantId, $product->id));
        return $product;
    }
}
