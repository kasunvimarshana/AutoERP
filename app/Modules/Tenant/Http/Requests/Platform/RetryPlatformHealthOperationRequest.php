<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class RetryPlatformHealthOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
