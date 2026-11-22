<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Selling\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\ItemSummaryResource;

final class SellingItemLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...(new ItemSummaryResource($this->resource))->resolve($request),
            'resolved_sales_unit_price' => $this->getAttribute('resolved_sales_unit_price'),
            'available_batches' => $this->getAttribute('available_batches') ?? [],
        ];
    }
}
