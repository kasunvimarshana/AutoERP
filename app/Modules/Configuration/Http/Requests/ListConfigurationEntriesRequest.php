<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

final class ListConfigurationEntriesRequest extends ViewConfigurationRequest
{
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'owner' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
