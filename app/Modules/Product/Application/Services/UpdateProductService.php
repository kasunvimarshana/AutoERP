<?php
namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductUpdated;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class UpdateProductService implements UpdateProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $repository) {}

    public function execute(Product $product, ProductData $data): Product
    {
        $updated = $this->repository->update($product, $data->toArray());
        Event::dispatch(new ProductUpdated($product->tenantId, $product->id));
        return $updated;
    }
}
