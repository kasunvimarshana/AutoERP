<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CustomerUserAccessRequest extends FormRequest
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
            'access_role' => ['nullable', 'string', 'max:60'],
            'is_primary' => ['nullable', 'boolean'],
            'invited' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'user' => ['required', 'array'],
            'user.name' => ['nullable', 'string', 'max:255'],
            'user.email' => ['required', 'email:rfc,dns', 'max:255'],
            'user.password' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }
}
