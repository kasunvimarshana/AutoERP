<?php

namespace Modules\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordCountedQtyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'   => ['required', 'uuid'],
            'counted_qty'  => ['required', 'numeric', 'min:0'],
            'expected_qty' => ['nullable', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ];
    }
}
