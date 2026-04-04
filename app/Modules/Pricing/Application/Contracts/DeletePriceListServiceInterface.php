<?php
namespace Modules\Pricing\Application\Contracts;
use Modules\Pricing\Domain\Entities\PriceList;

interface DeletePriceListServiceInterface
{
    public function execute(PriceList $priceList): bool;
}
