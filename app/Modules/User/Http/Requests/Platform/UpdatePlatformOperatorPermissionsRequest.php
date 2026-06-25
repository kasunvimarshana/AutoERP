<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePlatformOperatorPermissionsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'max:150'],
        ];
    }
}
