<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolvePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'max:50'],
            'price_list_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'item_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'warehouse_location_id' => ['nullable', 'integer'],
            'batch_id' => ['nullable', 'integer'],
            'serial_id' => ['nullable', 'integer'],
            'uom_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
        ];
    }
}
