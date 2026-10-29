<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\Enums\StockCardExportFormat;

class StockCardRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'timezone' => ['nullable', 'timezone'],
            'format' => [Rule::requiredIf($this->routeIs('api.v1.inventory.stock-card.export')), Rule::enum(StockCardExportFormat::class)],
        ];
    }
}
