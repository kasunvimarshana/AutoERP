<?php

declare(strict_types=1);

namespace Modules\Warehouse\Constants;

final class WarehouseErrorCode
{
    public const INVALID_VALUE = 'WAREHOUSE_INVALID_VALUE';

    public const NOT_FOUND = 'WAREHOUSE_NOT_FOUND';

    public const LOCATION_NOT_FOUND = 'WAREHOUSE_LOCATION_NOT_FOUND';

    public const DUPLICATE = 'WAREHOUSE_DUPLICATE';

    public const STALE_RECORD = 'WAREHOUSE_STALE_RECORD';

    public const SCOPE_MISMATCH = 'WAREHOUSE_SCOPE_MISMATCH';

    public const INVALID_HIERARCHY = 'WAREHOUSE_INVALID_HIERARCHY';

    public const UNSAFE_DELETE = 'WAREHOUSE_UNSAFE_DELETE';
}
