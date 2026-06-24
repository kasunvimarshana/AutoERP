<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class PlatformPermission
{
    public const TENANTS_VIEW = 'platform.tenants.view';
    public const TENANTS_CREATE = 'platform.tenants.create';
    public const TENANTS_UPDATE = 'platform.tenants.update';
    public const TENANTS_ONBOARD = 'platform.tenants.onboard';
    public const TENANTS_LIFECYCLE = 'platform.tenants.lifecycle';
    public const TENANT_DOMAINS_MANAGE = 'platform.tenant_domains.manage';
    public const TENANT_SUBSCRIPTIONS_MANAGE = 'platform.tenant_subscriptions.manage';
    public const PLANS_VIEW = 'platform.plans.view';
    public const PLANS_MANAGE = 'platform.plans.manage';
    public const CONFIGURATION_VIEW = 'platform.configuration.view';
    public const CONFIGURATION_MANAGE = 'platform.configuration.manage';
    public const SECRETS_MANAGE = 'platform.secrets.manage';
    public const AUDIT_VIEW = 'platform.audit.view';
    public const OPERATORS_MANAGE = 'platform.operators.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::TENANTS_VIEW => 'View SaaS tenants and onboarding readiness.',
            self::TENANTS_CREATE => 'Create draft SaaS tenants.',
            self::TENANTS_UPDATE => 'Update draft tenant identity and safe platform-managed fields.',
            self::TENANTS_ONBOARD => 'Provision tenant organization, access, authentication, and initial administrator invitation.',
            self::TENANTS_LIFECYCLE => 'Activate, suspend, deactivate, and archive tenants.',
            self::TENANT_DOMAINS_MANAGE => 'Create, verify, prioritize, disable, and remove tenant domains.',
            self::TENANT_SUBSCRIPTIONS_MANAGE => 'Assign and replace immutable tenant subscriptions.',
            self::PLANS_VIEW => 'View tenant plans and immutable plan revisions.',
            self::PLANS_MANAGE => 'Create, revise, and deactivate tenant plans.',
            self::CONFIGURATION_VIEW => 'View approved global configuration values and inheritance metadata.',
            self::CONFIGURATION_MANAGE => 'Manage approved global configuration values.',
            self::SECRETS_MANAGE => 'Manage encrypted platform and tenant infrastructure secrets.',
            self::AUDIT_VIEW => 'View platform and tenant audit records permitted to the operator.',
            self::OPERATORS_MANAGE => 'Manage platform operators and their permissions.',
        ];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_keys(self::descriptions());
    }

    private function __construct() {}
}
