<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\FindPriceListItemServiceInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class FindPriceListItemService extends BaseService implements FindPriceListItemServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $priceListItemRepository)
    {
        parent::__construct($priceListItemRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->priceListItemRepository->find($data['id'] ?? null);
    }
}
