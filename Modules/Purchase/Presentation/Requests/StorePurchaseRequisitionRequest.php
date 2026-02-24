<?php

namespace Modules\Purchase\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department'                    => ['nullable', 'string', 'max:255'],
            'required_by'                   => ['nullable', 'date'],
            'notes'                         => ['nullable', 'string'],
            'lines'                         => ['required', 'array', 'min:1'],
            'lines.*.product_id'            => ['required', 'uuid'],
            'lines.*.qty'                   => ['required', 'numeric', 'min:0.00000001'],
            'lines.*.unit_price'            => ['required', 'numeric', 'min:0'],
            'lines.*.uom'                   => ['nullable', 'string', 'max:50'],
            'lines.*.required_by_date'      => ['nullable', 'date'],
            'lines.*.justification'         => ['nullable', 'string'],
        ];
    }
}
