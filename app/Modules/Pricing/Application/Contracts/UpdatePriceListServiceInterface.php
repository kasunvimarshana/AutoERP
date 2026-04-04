<?php
namespace Modules\Pricing\Application\Contracts;
use Modules\Pricing\Domain\Entities\PriceList;

interface UpdatePriceListServiceInterface
{
    public function execute(PriceList $priceList, array $data): PriceList;
}
