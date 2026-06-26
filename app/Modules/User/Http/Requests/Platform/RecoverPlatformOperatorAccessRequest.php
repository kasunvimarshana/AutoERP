<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class RecoverPlatformOperatorAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
