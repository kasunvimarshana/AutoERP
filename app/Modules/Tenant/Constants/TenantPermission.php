<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantPermission
{
    public const PLATFORM_VIEW = 'tenant.platform.view';
    public const PLATFORM_MANAGE = 'tenant.platform.manage';
    public const PLATFORM_MANAGE_PLANS = 'tenant.platform.manage_plans';
    public const PLATFORM_MANAGE_LIFECYCLE = 'tenant.platform.manage_lifecycle';
    public const PROFILE_VIEW = 'tenant.profile.view';
    public const PROFILE_MANAGE = 'tenant.profile.manage';
    public const DOMAINS_VIEW = 'tenant.domains.view';
    public const DOMAINS_MANAGE = 'tenant.domains.manage';
    public const DOCUMENTS_VIEW = 'tenant.documents.view';
    public const DOCUMENTS_MANAGE = 'tenant.documents.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::PLATFORM_VIEW => 'View tenants across the SaaS platform.',
            self::PLATFORM_MANAGE => 'Create and update tenants across the SaaS platform.',
            self::PLATFORM_MANAGE_PLANS => 'Manage SaaS subscription plans.',
            self::PLATFORM_MANAGE_LIFECYCLE => 'Activate, suspend, deactivate, and archive tenants.',
            self::PROFILE_VIEW => 'View the active tenant profile.',
            self::PROFILE_MANAGE => 'Update safe self-service fields on the active tenant profile.',
            self::DOMAINS_VIEW => 'View domains owned by the active tenant.',
            self::DOMAINS_MANAGE => 'Create, verify, prioritize, and disable domains for the active tenant.',
            self::DOCUMENTS_VIEW => 'View and download private documents owned by the active tenant.',
            self::DOCUMENTS_MANAGE => 'Upload, replace, and remove private documents owned by the active tenant.',
        ];
    }

    private function __construct() {}
}
