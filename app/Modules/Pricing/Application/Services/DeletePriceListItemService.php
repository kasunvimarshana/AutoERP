<?php
namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class DeletePriceListItemService implements DeletePriceListItemServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $repository) {}

    public function execute(PriceListItem $item): bool
    {
        return $this->repository->delete($item);
    }
}
