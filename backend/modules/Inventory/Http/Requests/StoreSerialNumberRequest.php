<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\Enums\SerialNumberStatus;
use Illuminate\Validation\Rules\Enum;

class StoreSerialNumberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
            'batch_id' => ['nullable', 'uuid', 'exists:batches,id'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:serial_numbers,serial_number'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'status' => ['nullable', new Enum(SerialNumberStatus::class)],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'custom_attributes' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'variant_id' => 'product variant',
            'batch_id' => 'batch',
            'serial_number' => 'serial number',
            'warehouse_id' => 'warehouse',
            'location_id' => 'location',
            'purchase_cost' => 'purchase cost',
        ];
    }
}
