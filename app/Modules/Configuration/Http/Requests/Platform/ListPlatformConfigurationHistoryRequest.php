<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests\Platform;

final class ListPlatformConfigurationHistoryRequest extends ListPlatformConfigurationEntriesRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
