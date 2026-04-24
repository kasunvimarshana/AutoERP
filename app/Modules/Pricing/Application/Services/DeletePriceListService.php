<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;

class DeletePriceListService extends BaseService implements DeletePriceListServiceInterface
{
    public function __construct(
        private readonly PriceListRepositoryInterface $priceListRepository,
        private readonly PriceListItemRepositoryInterface $priceListItemRepository,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    )
    {
        parent::__construct($priceListRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $priceList = $this->priceListRepository->find($id);

        if (! $priceList) {
            throw new PriceListNotFoundException($id);
        }

        $tenantId = $priceList->getTenantId();
        $productIds = $this->priceListItemRepository->getDistinctProductIdsByPriceList($tenantId, $id);

        $deleted = $this->priceListRepository->delete($id);
        if ($deleted) {
            foreach ($productIds as $productId) {
                $this->refreshProjectionService->execute($tenantId, $productId);
            }
        }

        return $deleted;
    }
}
