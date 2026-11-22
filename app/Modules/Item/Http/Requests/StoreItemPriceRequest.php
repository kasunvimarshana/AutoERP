<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Item\Http\Requests;

final class StoreItemPriceRequest extends ItemPriceRequest
{
    public function toData(): \Modules\Item\DTOs\ItemPriceData
    {
        return $this->toPriceData();
    }
}
