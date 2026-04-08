<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
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
        $id = $data['id'];

        /** @var PriceList|null $priceList */
        $priceList = $this->priceListRepository->find($id);
        if (! $priceList) {
            throw new PriceListNotFoundException($id);
        }

        $this->priceListRepository->delete($id);

        return true;
    }
}
