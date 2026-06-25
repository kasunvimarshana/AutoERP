<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class ViewPlatformAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
