<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Modules\Configuration\Data\SystemPreferences;

interface SystemPreferencesResolverInterface
{
    /**
     * Resolve effective branding, localization, and operational system preferences.
     */
    public function resolve(int $tenantId, ?int $organizationUnitId = null): SystemPreferences;
}
