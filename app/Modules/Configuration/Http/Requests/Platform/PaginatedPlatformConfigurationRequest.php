<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests\Platform;

abstract class PaginatedPlatformConfigurationRequest extends ViewPlatformConfigurationRequest
{
    public function page(): int
    {
        return max(1, (int) $this->input('page', 1));
    }

    public function perPage(): int
    {
        return min(max((int) $this->input('per_page', 20), 1), 100);
    }
}
