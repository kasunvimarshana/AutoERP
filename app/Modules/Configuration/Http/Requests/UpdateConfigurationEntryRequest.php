<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

final class UpdateConfigurationEntryRequest extends ViewConfigurationRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'value' => ['present', 'nullable'],
        ];
    }
}
