<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;

final class FastPurchaseResource extends PurchaseResource
{
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $resource */
        $resource = $this->resource;

        return $resource;
    }
}
