<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Constants;

final class ReferenceDataPermission
{
    public const VIEW = 'reference_data.view';
    public const MANAGE = 'reference_data.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View active reference catalogs used by AutoERP.',
            self::MANAGE => 'Create, edit, activate, and deactivate reference catalog records.',
        ];
    }

    private function __construct() {}
}
