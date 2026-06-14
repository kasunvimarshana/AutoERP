<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class InventoryReservationResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'quantity_basis' => 'base',
        ];
    }
}
