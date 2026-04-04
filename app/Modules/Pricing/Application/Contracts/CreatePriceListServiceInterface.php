<?php
namespace Modules\Pricing\Application\Contracts;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Domain\Entities\PriceList;

interface CreatePriceListServiceInterface
{
    public function execute(PriceListData $data): PriceList;
}
