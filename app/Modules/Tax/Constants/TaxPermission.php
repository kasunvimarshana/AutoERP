<?php

declare(strict_types=1);

namespace Modules\Tax\Constants;

final class TaxPermission
{
    public const VIEW = 'tax.view';
    public const MANAGE = 'tax.manage';
    public const CALCULATE = 'tax.calculate';
    public const REPORTS_VIEW = 'tax.reports.view';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View taxes, rates, groups, profiles, and posting configuration.',
            self::MANAGE => 'Manage taxes, rates, groups, registrations, and posting configuration.',
            self::CALCULATE => 'Calculate taxes from governed document facts.',
            self::REPORTS_VIEW => 'View tax reports and reconciliation outputs.',
        ];
    }
}
