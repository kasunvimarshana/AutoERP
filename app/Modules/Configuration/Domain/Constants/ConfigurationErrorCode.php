<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Constants;

final class ConfigurationErrorCode
{
    public const NOT_FOUND = 'CONFIGURATION_NOT_FOUND';

    public const INVALID_KEY = 'CONFIGURATION_INVALID_KEY';

    public const INVALID_SOURCE = 'CONFIGURATION_INVALID_SOURCE';

    public const INVALID_VALUE = 'CONFIGURATION_INVALID_VALUE';

    public const INVALID_RECORD = 'CONFIGURATION_INVALID_RECORD';

    public const INVALID_SCOPE = 'CONFIGURATION_INVALID_SCOPE';

    public const TENANT_CONTEXT_REQUIRED = 'CONFIGURATION_TENANT_CONTEXT_REQUIRED';

    public const TRANSACTION_FAILED = 'CONFIGURATION_TRANSACTION_FAILED';

    private function __construct()
    {
    }
}
