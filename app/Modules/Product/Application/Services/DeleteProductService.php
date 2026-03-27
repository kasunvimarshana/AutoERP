<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Domain\Events\ProductDeleted;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class DeleteProductService extends BaseService implements DeleteProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
        parent::__construct($productRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $product = $this->productRepository->find($id);

        if (! $product) {
            throw new ProductNotFoundException($id);
        }

        $tenantId = $product->getTenantId();
        $deleted = $this->productRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new ProductDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
