<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\ActivatePriceListServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Events\PriceListActivated;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class ActivatePriceListService extends BaseService implements ActivatePriceListServiceInterface
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

        $priceList->activate();
        $saved = $this->priceListRepository->save($priceList);
        $this->addEvent(new PriceListActivated($saved));

        return $saved;
    }
}
