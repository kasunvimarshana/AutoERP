<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Constants;

final class ConfigurationScope
{
    public const GLOBAL = 'global';

    public const TENANT = 'tenant';

    public const ORGANIZATION_UNIT = 'organization_unit';

    private function __construct()
    {
    }
}
