<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Constants;

final class InventoryDimension
{
    public const TENANT_ID = 'tenant_id';
    public const ORGANIZATION_UNIT_ID = 'organization_unit_id';
    public const WAREHOUSE_ID = 'warehouse_id';
    public const LOCATION_ID = 'location_id';
    public const ITEM_ID = 'item_id';
    public const VARIANT_ID = 'variant_id';
    public const BATCH_ID = 'batch_id';
    public const LOT_NUMBER = 'lot_number';
    public const SERIAL_ID = 'serial_id';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::TENANT_ID,
            self::ORGANIZATION_UNIT_ID,
            self::WAREHOUSE_ID,
            self::LOCATION_ID,
            self::ITEM_ID,
            self::VARIANT_ID,
            self::BATCH_ID,
            self::LOT_NUMBER,
            self::SERIAL_ID,
        ];
    }
}
