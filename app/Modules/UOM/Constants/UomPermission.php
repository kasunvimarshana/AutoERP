<?php

declare(strict_types=1);

namespace Modules\UOM\Constants;

final class UomPermission
{
    public const UOMS_VIEW = 'uom.view';
    public const UOMS_CREATE = 'uom.create';
    public const UOMS_UPDATE = 'uom.update';
    public const UOMS_ACTIVATE = 'uom.activate';
    public const UOMS_DEACTIVATE = 'uom.deactivate';
    public const UOMS_DELETE = 'uom.delete';
    public const CONVERSIONS_VIEW = 'uom.conversions.view';
    public const CONVERSIONS_CREATE = 'uom.conversions.create';
    public const CONVERSIONS_UPDATE = 'uom.conversions.update';
    public const CONVERSIONS_ACTIVATE = 'uom.conversions.activate';
    public const CONVERSIONS_DEACTIVATE = 'uom.conversions.deactivate';
    public const CONVERSIONS_DELETE = 'uom.conversions.delete';
    public const CONVERSIONS_RUN = 'uom.conversions.run';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::UOMS_VIEW => 'View units of measure and UOM lookup data.',
            self::UOMS_CREATE => 'Create units of measure.',
            self::UOMS_UPDATE => 'Update unit of measure setup fields.',
            self::UOMS_ACTIVATE => 'Activate units of measure for operational use.',
            self::UOMS_DEACTIVATE => 'Deactivate units of measure while preserving historical references.',
            self::UOMS_DELETE => 'Delete unused units of measure.',
            self::CONVERSIONS_VIEW => 'View UOM conversion rules.',
            self::CONVERSIONS_CREATE => 'Create UOM conversion rules.',
            self::CONVERSIONS_UPDATE => 'Update UOM conversion rules.',
            self::CONVERSIONS_ACTIVATE => 'Activate UOM conversion rules.',
            self::CONVERSIONS_DEACTIVATE => 'Deactivate UOM conversion rules.',
            self::CONVERSIONS_DELETE => 'Delete unused UOM conversion rules.',
            self::CONVERSIONS_RUN => 'Run UOM conversion calculations.',
        ];
    }
}
