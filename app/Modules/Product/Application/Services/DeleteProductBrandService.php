<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductBrandServiceInterface;
use Modules\Product\Domain\Exceptions\ProductBrandNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductBrandRepositoryInterface;

class DeleteProductBrandService extends BaseService implements DeleteProductBrandServiceInterface
{
    public function __construct(private readonly ProductBrandRepositoryInterface $productBrandRepository)
    {
        parent::__construct($productBrandRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $productBrand = $this->productBrandRepository->find($id);

        if (! $productBrand) {
            throw new ProductBrandNotFoundException($id);
        }

        return $this->productBrandRepository->delete($id);
    }
}
