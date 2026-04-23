<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DraftProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class DraftProductService extends BaseService implements DraftProductServiceInterface
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

        $product->setStatus('draft');

        return $this->productRepository->save($product);
    }
}
