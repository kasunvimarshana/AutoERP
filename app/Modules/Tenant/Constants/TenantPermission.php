<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantPermission
{
    public const PROFILE_VIEW = 'tenant.profile.view';
    public const PROFILE_MANAGE = 'tenant.profile.manage';
    public const CROSS_ORG_POLICY_MANAGE = 'tenant.policy.cross_org_transactions.manage';
    public const DOMAINS_VIEW = 'tenant.domains.view';
    public const DOMAINS_MANAGE = 'tenant.domains.manage';
    public const DOCUMENTS_VIEW = 'tenant.documents.view';
    public const DOCUMENTS_MANAGE = 'tenant.documents.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::PROFILE_VIEW => 'View the active tenant profile.',
            self::PROFILE_MANAGE => 'Update safe self-service fields on the active tenant profile.',
            self::CROSS_ORG_POLICY_MANAGE => 'Enable or disable transactions across organization units for the active tenant.',
            self::DOMAINS_VIEW => 'View domains owned by the active tenant.',
            self::DOMAINS_MANAGE => 'Create, verify, prioritize, and disable domains for the active tenant.',
            self::DOCUMENTS_VIEW => 'View and download private documents owned by the active tenant.',
            self::DOCUMENTS_MANAGE => 'Upload, replace, and remove private documents owned by the active tenant.',
        ];
    }

    private function __construct() {}
}
