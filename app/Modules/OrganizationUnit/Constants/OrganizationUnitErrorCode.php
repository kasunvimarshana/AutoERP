<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Constants;

final class OrganizationUnitErrorCode
{
    public const NOT_FOUND = 'ORGANIZATION_UNIT_NOT_FOUND';
    public const TENANT_NOT_FOUND = 'ORGANIZATION_UNIT_TENANT_NOT_FOUND';
    public const TENANT_MISMATCH = 'ORGANIZATION_UNIT_TENANT_MISMATCH';
    public const PLAN_LIMIT_REACHED = 'ORGANIZATION_UNIT_PLAN_LIMIT_REACHED';
    public const CONFLICT = 'ORGANIZATION_UNIT_CONFLICT';
    public const INVALID_VALUE = 'ORGANIZATION_UNIT_INVALID_VALUE';
    public const VERSION_CONFLICT = 'ORGANIZATION_UNIT_VERSION_CONFLICT';
    public const LIFECYCLE_BLOCKED = 'ORGANIZATION_UNIT_LIFECYCLE_BLOCKED';

    private function __construct() {}
}
