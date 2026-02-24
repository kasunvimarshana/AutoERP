<?php
namespace Modules\Sales\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePriceListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'currency_code'  => 'required|string|size:3',
            'is_active'      => 'sometimes|boolean',
            'valid_from'     => 'nullable|date',
            'valid_to'       => 'nullable|date|after:valid_from',
            'customer_group' => 'nullable|string|max:100',
        ];
    }
}
