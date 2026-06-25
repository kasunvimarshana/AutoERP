<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class ViewPlatformConfigurationRequest extends FormRequest
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
