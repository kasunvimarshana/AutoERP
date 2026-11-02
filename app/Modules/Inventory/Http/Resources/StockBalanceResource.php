<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        return [
            ...$data,
            'quantity_basis' => 'base',
            'base_quantity_on_hand' => $data['quantity_on_hand'] ?? '0.000000',
            'base_quantity_reserved' => $data['quantity_reserved'] ?? '0.000000',
            'base_quantity_allocated' => $data['quantity_allocated'] ?? '0.000000',
            'base_quantity_available' => $data['quantity_available'] ?? '0.000000',
        ];
    }
}
