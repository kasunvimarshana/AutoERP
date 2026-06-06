<?php

declare(strict_types=1);

namespace Modules\Configuration\Constants;

final class ConfigurationDefaults
{
    public const DEFAULT_PAGE = 1;

    public const DEFAULT_PER_PAGE = 50;

    public const MAX_PER_PAGE = 500;

    public const DEFAULT_CACHE_TTL_SECONDS = 300;

    private function __construct() {}
}
