<?php

declare(strict_types=1);

namespace Modules\Configuration\Constants;

final class ConfigurationSource
{
    public const DATABASE = 'database';

    public const ENVIRONMENT = 'environment';

    public const RUNTIME = 'runtime';

    private function __construct() {}
}
