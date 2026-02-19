<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Inventory\Models\StockMovement;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockMovement::class);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->currentTenant()->id;

        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'from_warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'to_warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'from_location_id' => [
                'nullable',
                Rule::exists('stock_locations', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'to_location_id' => [
                'nullable',
                Rule::exists('stock_locations', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'batch_lot_id' => [
                'nullable',
                Rule::exists('batch_lots', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'serial_number_id' => [
                'nullable',
                Rule::exists('serial_numbers', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'movement_date' => ['nullable', 'date'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'from_warehouse_id' => 'from warehouse',
            'to_warehouse_id' => 'to warehouse',
            'from_location_id' => 'from location',
            'to_location_id' => 'to location',
            'batch_lot_id' => 'batch/lot',
            'serial_number_id' => 'serial number',
            'movement_date' => 'movement date',
            'document_number' => 'document number',
        ];
    }
}
