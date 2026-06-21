<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

final class CreateConfigurationEntryRequest extends ViewConfigurationRequest
{
    public function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)+$/',
            ],
            'value' => ['present', 'nullable'],
        ];
    }
}
