<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Domain\Constants\UserStatus;

final class UpsertUserRequest extends FormRequest
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
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'identity_references' => ['nullable', 'array'],
            'identity_references.*' => ['required_with:identity_references', 'string', 'max:255'],
            'first_name' => array_merge($required, ['string', 'max:255']),
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => array_merge($required, ['email:rfc,dns', 'max:255']),
            'email_verified_at' => ['nullable', 'date'],
            'password' => array_merge($required, ['string', 'min:1']),
            'status' => ['nullable', 'string', 'in:' . implode(',', UserStatus::values())],
            'avatar_path' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'preferences' => ['nullable', 'array'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
