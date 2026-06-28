<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

final class StoreItemPriceRequest extends ItemPriceRequest
{
    public function toData(): \Modules\Item\DTOs\ItemPriceData
    {
        return $this->toPriceData();
    }
}
