<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantErrorCode
{
    public const NOT_FOUND = 'TENANT_NOT_FOUND';

    public const INVALID_VALUE = 'TENANT_INVALID_VALUE';

    public const DUPLICATE_CODE = 'TENANT_DUPLICATE_CODE';

    public const DUPLICATE_ISOLATION_KEY = 'TENANT_DUPLICATE_ISOLATION_KEY';

    public const CONFLICT = 'TENANT_CONFLICT';

    private function __construct() {}
}
