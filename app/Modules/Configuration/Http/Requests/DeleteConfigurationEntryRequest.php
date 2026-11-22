<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

final class DeleteConfigurationEntryRequest extends ManageConfigurationEntryRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
