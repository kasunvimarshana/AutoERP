<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmPlatformMfaEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'enrollment_proof' => ['required', 'string', 'max:512'],
            'code' => ['required', 'string', 'regex:/^\\d{6}$/'],
            'email' => ['prohibited'],
            'password' => ['prohibited'],
            'operator_id' => ['prohibited'],
        ];
    }
}
