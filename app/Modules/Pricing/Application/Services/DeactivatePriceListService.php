<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\DeactivatePriceListServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Events\PriceListDeactivated;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class DeactivatePriceListService extends BaseService implements DeactivatePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $priceListRepository)
    {
        parent::__construct($priceListRepository);
    }

    protected function handle(array $data): PriceList
    {
        $id = $data['id'];

        /** @var PriceList|null $priceList */
        $priceList = $this->priceListRepository->find($id);
        if (! $priceList) {
            throw new PriceListNotFoundException($id);
        }

        $priceList->deactivate();
        $saved = $this->priceListRepository->save($priceList);
        $this->addEvent(new PriceListDeactivated($saved));

        return $saved;
    }
}
