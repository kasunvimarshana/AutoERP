<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class DeletePriceListService extends BaseService implements DeletePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $priceListRepository)
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

        return $this->priceListRepository->delete($id);
    }
}
