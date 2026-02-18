<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkRegisterSerialNumbersRequest extends FormRequest
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
            'serial_numbers' => ['required', 'array', 'min:1'],
            'serial_numbers.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'serial_numbers.*.variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
            'serial_numbers.*.batch_id' => ['nullable', 'uuid', 'exists:batches,id'],
            'serial_numbers.*.serial_number' => ['required', 'string', 'max:255'],
            'serial_numbers.*.warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'serial_numbers.*.location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'serial_numbers.*.purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'serial_numbers.*.notes' => ['nullable', 'string'],
        ];
    }
}
