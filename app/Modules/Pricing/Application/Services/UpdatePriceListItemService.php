<?php
namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class UpdatePriceListItemService implements UpdatePriceListItemServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $repository) {}

    public function execute(PriceListItem $item, array $data): PriceListItem
    {
        return $this->repository->update($item, $data);
    }
}
