<?php

namespace Modules\Contracts\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'type'            => ['nullable', 'string', 'in:service,subscription,maintenance,supply,other'],
            'party_name'      => ['required', 'string', 'max:255'],
            'party_email'     => ['nullable', 'email', 'max:255'],
            'party_reference' => ['nullable', 'string', 'max:255'],
            'start_date'      => ['nullable', 'date'],
            'end_date'        => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_value'     => ['nullable', 'numeric', 'min:0'],
            'currency'        => ['nullable', 'string', 'size:3'],
            'payment_terms'   => ['nullable', 'string', 'max:500'],
            'notes'           => ['nullable', 'string'],
        ];
    }
}
