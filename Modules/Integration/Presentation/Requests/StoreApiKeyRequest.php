<?php

namespace Modules\Integration\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'scopes'     => ['nullable', 'array'],
            'scopes.*'   => ['string'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
