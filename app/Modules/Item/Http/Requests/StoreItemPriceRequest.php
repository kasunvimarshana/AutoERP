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

use Modules\Item\DTOs\ItemPriceData;

final class StoreItemPriceRequest extends ItemPriceRequest
{
    public function toData(): ItemPriceData
    {
        return $this->toPriceData();
    }
}
