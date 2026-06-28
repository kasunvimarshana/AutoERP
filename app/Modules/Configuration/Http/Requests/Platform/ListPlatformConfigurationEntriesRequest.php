<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests\Platform;

final class ListPlatformConfigurationEntriesRequest extends PaginatedPlatformConfigurationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'owner' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

}
