<?php

declare(strict_types=1);

namespace Modules\Document\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Document\Enums\PermissionType;

class ShareDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,id'],
            'permission_type' => ['required', 'string', 'in:'.implode(',', array_column(PermissionType::cases(), 'value'))],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required',
            'user_id.exists' => 'Selected user does not exist',
            'permission_type.required' => 'Permission type is required',
            'expires_at.after' => 'Expiration date must be in the future',
        ];
    }
}
