<?php
namespace Modules\Pricing\Application\Contracts;
use Modules\Pricing\Domain\Entities\PriceListItem;

interface UpdatePriceListItemServiceInterface
{
    public function execute(PriceListItem $item, array $data): PriceListItem;
}
