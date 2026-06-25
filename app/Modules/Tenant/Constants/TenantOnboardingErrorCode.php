<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantOnboardingErrorCode
{
    public const OPERATION_IN_PROGRESS = 'TENANT_ONBOARDING_IN_PROGRESS';
    public const EMAIL_CONFLICT = 'TENANT_ADMINISTRATOR_EMAIL_CONFLICT';
    public const VERSION_CONFLICT = 'TENANT_ONBOARDING_VERSION_CONFLICT';
    public const ROOT_ORGANIZATION_FAILED = 'TENANT_ROOT_ORGANIZATION_FAILED';
    public const ACCESS_PROVISIONING_FAILED = 'TENANT_ACCESS_PROVISIONING_FAILED';
    public const AUTHENTICATION_PROVIDER_FAILED = 'TENANT_AUTHENTICATION_PROVIDER_FAILED';
    public const ADMINISTRATOR_INVITATION_FAILED = 'TENANT_ADMINISTRATOR_INVITATION_FAILED';
    public const FINALIZATION_FAILED = 'TENANT_ONBOARDING_FINALIZATION_FAILED';
    public const FOUNDATION_INCOMPLETE = 'TENANT_FOUNDATION_INCOMPLETE';

    public static function forStep(string $step): string
    {
        return match ($step) {
            TenantOnboardingStep::ROOT_ORGANIZATION => self::ROOT_ORGANIZATION_FAILED,
            TenantOnboardingStep::PERMISSION_CATALOGUE,
            TenantOnboardingStep::SUPER_ADMIN_ROLE => self::ACCESS_PROVISIONING_FAILED,
            TenantOnboardingStep::AUTHENTICATION_PROVIDER => self::AUTHENTICATION_PROVIDER_FAILED,
            TenantOnboardingStep::INITIAL_ADMIN_INVITATION => self::ADMINISTRATOR_INVITATION_FAILED,
            default => self::FINALIZATION_FAILED,
        };
    }

    public static function safeMessage(string $step): string
    {
        return match ($step) {
            TenantOnboardingStep::ROOT_ORGANIZATION => 'The root organization could not be provisioned.',
            TenantOnboardingStep::PERMISSION_CATALOGUE,
            TenantOnboardingStep::SUPER_ADMIN_ROLE => 'Tenant access could not be provisioned.',
            TenantOnboardingStep::AUTHENTICATION_PROVIDER => 'The authentication provider could not be provisioned.',
            TenantOnboardingStep::INITIAL_ADMIN_INVITATION => 'The initial administrator invitation could not be created or delivered.',
            default => 'Tenant foundation provisioning could not be completed.',
        };
    }

    private function __construct() {}
}
