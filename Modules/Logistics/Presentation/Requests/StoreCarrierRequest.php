<?php

namespace Modules\Logistics\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'code'         => ['required', 'string', 'alpha_dash', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'email'        => ['nullable', 'email'],
            'is_active'    => ['nullable', 'boolean'],
        ];
    }
}
