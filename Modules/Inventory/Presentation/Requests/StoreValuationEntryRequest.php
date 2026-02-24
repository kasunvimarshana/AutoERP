<?php

namespace Modules\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreValuationEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'uuid'],
            'movement_type'    => ['required', 'in:receipt,deduction,adjustment'],
            'qty'              => ['required', 'numeric', 'min:0.00000001'],
            'unit_cost'        => ['required', 'numeric', 'min:0'],
            'valuation_method' => ['sometimes', 'in:fifo,weighted_average'],
            'reference_type'   => ['nullable', 'string', 'max:50'],
            'reference_id'     => ['nullable', 'uuid'],
        ];
    }
}
