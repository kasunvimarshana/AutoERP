<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantErrorCode
{
    public const NOT_FOUND = 'TENANT_NOT_FOUND';
    public const INVALID_VALUE = 'TENANT_INVALID_VALUE';
    public const DUPLICATE_CODE = 'TENANT_DUPLICATE_CODE';
    public const DUPLICATE_DOMAIN = 'TENANT_DUPLICATE_DOMAIN';
    public const CONFLICT = 'TENANT_CONFLICT';
    public const VERSION_CONFLICT = 'TENANT_VERSION_CONFLICT';
    public const INVALID_TRANSITION = 'TENANT_INVALID_TRANSITION';
    public const DOMAIN_NOT_VERIFIED = 'TENANT_DOMAIN_NOT_VERIFIED';
    public const FILE_OPERATION_FAILED = 'TENANT_FILE_OPERATION_FAILED';
    public const SUBSCRIPTION_DATA_UNAVAILABLE = 'TENANT_SUBSCRIPTION_DATA_UNAVAILABLE';
    public const SCHEMA_INCOMPATIBLE = 'TENANT_SCHEMA_INCOMPATIBLE';

    private function __construct() {}
}
