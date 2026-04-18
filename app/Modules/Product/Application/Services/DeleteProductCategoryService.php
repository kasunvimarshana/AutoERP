<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Domain\Exceptions\ProductCategoryNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class DeleteProductCategoryService extends BaseService implements DeleteProductCategoryServiceInterface
{
    public function __construct(private readonly ProductCategoryRepositoryInterface $productCategoryRepository)
    {
        parent::__construct($productCategoryRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $productCategory = $this->productCategoryRepository->find($id);

        if (! $productCategory) {
            throw new ProductCategoryNotFoundException($id);
        }

        return $this->productCategoryRepository->delete($id);
    }
}
