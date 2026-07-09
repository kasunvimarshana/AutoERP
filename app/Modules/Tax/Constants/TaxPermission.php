<?php

declare(strict_types=1);

namespace Modules\Tax\Constants;

final class TaxPermission
{
    public const LOOKUPS_VIEW = 'tax.lookups.view';
    public const CALCULATIONS_RUN = 'tax.calculations.run';
    public const TAXES_VIEW = 'tax.taxes.view';
    public const TAXES_MANAGE = 'tax.taxes.manage';
    public const GROUPS_VIEW = 'tax.groups.view';
    public const GROUPS_MANAGE = 'tax.groups.manage';
    public const PROFILES_VIEW = 'tax.profiles.view';
    public const PROFILES_MANAGE = 'tax.profiles.manage';
    public const POSTING_PROFILES_VIEW = 'tax.posting_profiles.view';
    public const POSTING_PROFILES_MANAGE = 'tax.posting_profiles.manage';
    public const REPORTS_VIEW = 'tax.reports.view';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::LOOKUPS_VIEW => 'View tax lookup data for configured tax workflows.',
            self::CALCULATIONS_RUN => 'Run tax calculations for document previews and validation.',
            self::TAXES_VIEW => 'View tax definitions and effective rates.',
            self::TAXES_MANAGE => 'Create and update tax definitions and effective rates.',
            self::GROUPS_VIEW => 'View tax groups and tax determination bundles.',
            self::GROUPS_MANAGE => 'Create and update tax groups and tax determination bundles.',
            self::PROFILES_VIEW => 'View customer and supplier tax profiles.',
            self::PROFILES_MANAGE => 'Create and update customer and supplier tax profiles.',
            self::POSTING_PROFILES_VIEW => 'View tax posting profiles.',
            self::POSTING_PROFILES_MANAGE => 'Create and update tax posting profiles.',
            self::REPORTS_VIEW => 'View tax reports and tax transaction summaries.',
        ];
    }
}
