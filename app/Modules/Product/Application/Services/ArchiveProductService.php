<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\ArchiveProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class ArchiveProductService extends BaseService implements ArchiveProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
        parent::__construct($productRepository);
    }

    protected function handle(array $data): Product
    {
        $id = (int) ($data['id'] ?? 0);
        $product = $this->productRepository->find($id);

        if (! $product) {
            throw new ProductNotFoundException($id);
        }

        $product->setStatus('archived');

        return $this->productRepository->save($product);
    }
}
