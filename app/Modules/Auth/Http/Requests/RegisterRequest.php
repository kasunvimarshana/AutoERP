<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
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
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'provider_key' => ['nullable', 'string', 'max:120'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'password' => ['required', 'string', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
