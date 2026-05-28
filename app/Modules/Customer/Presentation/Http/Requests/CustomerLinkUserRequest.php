<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CustomerLinkUserRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'min:1'],
            'access_role' => ['nullable', 'string', 'max:60'],
            'is_primary' => ['nullable', 'boolean'],
            'invited' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}