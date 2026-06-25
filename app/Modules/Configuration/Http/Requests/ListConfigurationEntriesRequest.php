<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

final class ListConfigurationEntriesRequest extends ViewConfigurationRequest
{
    public function rules(): array
    {
        return [
            'prefix' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*[._-]?$/',
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
