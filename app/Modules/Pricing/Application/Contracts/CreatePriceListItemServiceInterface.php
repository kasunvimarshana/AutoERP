<?php
namespace Modules\Pricing\Application\Contracts;
use Modules\Pricing\Application\DTOs\PriceListItemData;
use Modules\Pricing\Domain\Entities\PriceListItem;

interface CreatePriceListItemServiceInterface
{
    public function execute(PriceListItemData $data): PriceListItem;
}
