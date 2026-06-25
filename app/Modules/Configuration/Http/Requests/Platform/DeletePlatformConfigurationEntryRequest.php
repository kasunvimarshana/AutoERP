<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests\Platform;

final class DeletePlatformConfigurationEntryRequest extends ViewPlatformConfigurationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['expected_version' => ['required', 'integer', 'min:1']];
    }
}
