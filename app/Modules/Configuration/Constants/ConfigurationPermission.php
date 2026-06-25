<?php

declare(strict_types=1);

namespace Modules\Configuration\Constants;

final class ConfigurationPermission
{
    public const ENTRIES_VIEW = 'configuration.entries.view';
    public const ENTRIES_MANAGE_TENANT = 'configuration.entries.manage_tenant';
    public const ENTRIES_MANAGE_ORGANIZATION = 'configuration.entries.manage_organization';
    public const ENTRIES_MANAGE_SENSITIVE = 'configuration.entries.manage_sensitive';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::ENTRIES_VIEW => 'View registered configuration definitions and scoped overrides.',
            self::ENTRIES_MANAGE_TENANT => 'Manage configuration overrides for the active tenant.',
            self::ENTRIES_MANAGE_ORGANIZATION => 'Manage configuration overrides for the active organization unit.',
            self::ENTRIES_MANAGE_SENSITIVE => 'Create or rotate protected configuration values.',
        ];
    }

    private function __construct() {}
}
