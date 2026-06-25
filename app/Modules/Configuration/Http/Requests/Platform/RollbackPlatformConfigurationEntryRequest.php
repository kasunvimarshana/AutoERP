<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests\Platform;

final class RollbackPlatformConfigurationEntryRequest extends ViewPlatformConfigurationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'revision_id' => ['required', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
