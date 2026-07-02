<?php

declare(strict_types=1);

namespace Modules\Core\Authorization;

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
    public const AUDIT_SENSITIVE_VIEW = 'platform.audit.sensitive.view';
    public const OPERATORS_VIEW = 'platform.operators.view';
    public const OPERATORS_MANAGE = 'platform.operators.manage';
    public const SESSIONS_VIEW = 'platform.sessions.view';
    public const SESSIONS_MANAGE = 'platform.sessions.manage';
    public const HEALTH_VIEW = 'platform.health.view';
    public const HEALTH_MANAGE = 'platform.health.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::TENANTS_VIEW => 'View SaaS tenants and onboarding readiness.',
            self::TENANTS_CREATE => 'Create draft SaaS tenants.',
            self::TENANTS_UPDATE => 'Update draft tenant identity and safe platform-managed fields.',
            self::TENANTS_ONBOARD => 'Provision tenant organization, access, authentication, and initial administrator account.',
            self::TENANTS_LIFECYCLE => 'Activate, suspend, deactivate, and archive tenants.',
            self::TENANT_DOMAINS_MANAGE => 'Create, verify, prioritize, disable, and remove tenant domains.',
            self::TENANT_SUBSCRIPTIONS_MANAGE => 'Manage immutable tenant subscription contracts and lifecycle.',
            self::PLANS_VIEW => 'View tenant plans and immutable plan revisions.',
            self::PLANS_MANAGE => 'Create, revise, activate, and deactivate tenant plans.',
            self::CONFIGURATION_VIEW => 'View approved configuration values and inheritance metadata.',
            self::CONFIGURATION_MANAGE => 'Manage approved platform, tenant, and organization configuration values.',
            self::SECRETS_MANAGE => 'Manage encrypted infrastructure secrets and connection profiles.',
            self::AUDIT_VIEW => 'View platform and tenant audit records permitted to the operator.',
            self::AUDIT_SENSITIVE_VIEW => 'View sensitive audit details that are hidden from standard audit readers.',
            self::OPERATORS_VIEW => 'View platform operators and effective permissions.',
            self::OPERATORS_MANAGE => 'Create, activate, deactivate, and govern platform operators.',
            self::SESSIONS_VIEW => 'View platform operator sessions.',
            self::SESSIONS_MANAGE => 'Revoke platform operator sessions.',
            self::HEALTH_VIEW => 'View platform tenant, domain, subscription, storage, queue, and outbox health.',
            self::HEALTH_MANAGE => 'Retry and recover failed platform operations with an audited reason.',
        ];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_keys(self::descriptions());
    }

    private function __construct() {}
}
