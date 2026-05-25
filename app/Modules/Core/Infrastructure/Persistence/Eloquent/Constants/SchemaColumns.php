<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Constants;

final class SchemaColumns
{
    public const ID = 'id';
    public const UUID = 'uuid';
    public const TENANT_ID = 'tenant_id';
    public const ORGANIZATION_UNIT_ID = 'organization_unit_id';
    public const STATUS = 'status';
    public const IS_ACTIVE = 'is_active';
    public const REFERENCE = 'code';

    private function __construct()
    {
    }
}
