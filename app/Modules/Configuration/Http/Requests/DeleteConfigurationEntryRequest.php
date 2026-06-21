<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

final class DeleteConfigurationEntryRequest extends ViewConfigurationRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
