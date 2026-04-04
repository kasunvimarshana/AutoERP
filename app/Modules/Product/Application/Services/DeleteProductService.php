<?php
namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductDeleted;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class DeleteProductService implements DeleteProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $repository) {}

    public function execute(Product $product): bool
    {
        $result = $this->repository->delete($product);
        if ($result) {
            Event::dispatch(new ProductDeleted($product->tenantId, $product->id));
        }
        return $result;
    }
}
