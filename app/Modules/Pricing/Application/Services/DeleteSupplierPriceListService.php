<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\DeleteSupplierPriceListServiceInterface;
use Modules\Pricing\Domain\Exceptions\SupplierPriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\SupplierPriceListRepositoryInterface;

class DeleteSupplierPriceListService extends BaseService implements DeleteSupplierPriceListServiceInterface
{
    public function __construct(private readonly SupplierPriceListRepositoryInterface $supplierPriceListRepository)
    {
        parent::__construct($supplierPriceListRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $assignment = $this->supplierPriceListRepository->find($id);

        if (! $assignment) {
            throw new SupplierPriceListNotFoundException($id);
        }

        return $this->supplierPriceListRepository->delete($id);
    }
}
