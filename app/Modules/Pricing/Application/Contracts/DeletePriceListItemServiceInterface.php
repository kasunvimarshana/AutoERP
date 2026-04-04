<?php
namespace Modules\Pricing\Application\Contracts;
use Modules\Pricing\Domain\Entities\PriceListItem;

interface DeletePriceListItemServiceInterface
{
    public function execute(PriceListItem $item): bool;
}
