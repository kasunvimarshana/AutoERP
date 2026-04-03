<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class FindPriceListService extends BaseService implements FindPriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $priceListRepository)
    {
        parent::__construct($priceListRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->priceListRepository->find($data['id'] ?? null);
    }
}
