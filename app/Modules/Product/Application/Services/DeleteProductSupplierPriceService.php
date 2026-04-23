<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductSupplierPriceServiceInterface;
use Modules\Product\Domain\Exceptions\ProductSupplierPriceNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductSupplierPriceRepositoryInterface;

class DeleteProductSupplierPriceService extends BaseService implements DeleteProductSupplierPriceServiceInterface
{
    public function __construct(private readonly ProductSupplierPriceRepositoryInterface $productSupplierPriceRepository)
    {
        parent::__construct($productSupplierPriceRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->productSupplierPriceRepository->find($id);

        if (! $entity) {
            throw new ProductSupplierPriceNotFoundException($id);
        }

        return $this->productSupplierPriceRepository->delete($id);
    }
}
