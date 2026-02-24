<?php

namespace Modules\Accounting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'partner_id'            => ['nullable', 'uuid'],
            'partner_type'          => ['nullable', 'string', 'in:customer,vendor'],
            'currency'              => ['nullable', 'string', 'size:3'],
            'due_date'              => ['nullable', 'date'],
            'notes'                 => ['nullable', 'string'],
            'lines'                 => ['required', 'array', 'min:1'],
            'lines.*.description'   => ['required', 'string', 'max:255'],
            'lines.*.quantity'      => ['required', 'numeric', 'min:0.00000001'],
            'lines.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate'      => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }
}
