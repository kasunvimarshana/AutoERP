<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LinkExistingEmployeeUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
