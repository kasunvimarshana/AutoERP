<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Inventory\Enums\StockCountStatus;

class UpdateStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('stockCount'));
    }

    public function rules(): array
    {
        $tenantId = $this->user()->currentTenant()->id;
        $stockCount = $this->route('stockCount');

        return [
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'count_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('stock_counts', 'count_number')
                    ->where('tenant_id', $tenantId)
                    ->ignore($stockCount->id)
                    ->whereNull('deleted_at'),
            ],
            'status' => ['nullable', Rule::enum(StockCountStatus::class)],
            'count_date' => ['nullable', 'date'],
            'scheduled_date' => ['nullable', 'date'],
            'counted_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'items.*.location_id' => [
                'nullable',
                Rule::exists('stock_locations', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'items.*.system_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'warehouse',
            'count_number' => 'count number',
            'count_date' => 'count date',
            'scheduled_date' => 'scheduled date',
            'counted_by' => 'counted by',
            'items.*.product_id' => 'product',
            'items.*.location_id' => 'location',
            'items.*.system_quantity' => 'system quantity',
            'items.*.counted_quantity' => 'counted quantity',
        ];
    }
}
