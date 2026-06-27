<?php

declare(strict_types=1);

namespace Modules\Hr\Constants;

final class HrPermission
{
    public const VIEW = 'hr.view';
    public const MANAGE = 'hr.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View employees and human-resources reference data.',
            self::MANAGE => 'Manage employees, assignments, qualifications, rates, and availability.',
        ];
    }
}
