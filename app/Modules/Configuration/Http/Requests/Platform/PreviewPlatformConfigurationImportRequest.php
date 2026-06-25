<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class PreviewPlatformConfigurationImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'document' => ['required', 'array'],
            'document.schema_version' => ['required', 'integer'],
            'document.scope' => ['required', 'string'],
            'document.entries' => ['required', 'array', 'max:500'],
            'document.entries.*' => ['required', 'array'],
            'document.entries.*.key' => ['required', 'string', 'max:190'],
            'document.entries.*.value' => ['present'],
        ];
    }
}
