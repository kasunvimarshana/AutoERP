<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantReadinessCheck
{
    public const ROOT_ORGANIZATION = 'root_organization';
    public const PERMISSION_CATALOGUE = 'permission_catalogue';
    public const SUPER_ADMIN_ACCESS = 'super_admin_access';
    public const AUTHENTICATION_PROVIDER = 'authentication_provider';
    public const ADMINISTRATOR_INVITATION_ACCEPTED = 'administrator_invitation_accepted';
    public const OPERATIONAL_ADMINISTRATOR = 'operational_administrator';
    public const BASE_CURRENCY = 'base_currency';
    public const ACTIVE_PLAN = 'active_plan';
    public const SUBSCRIPTION_VALID = 'subscription_valid';
    public const PRIMARY_DOMAIN_READY = 'primary_domain_ready';

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            self::ROOT_ORGANIZATION => 'Create a valid protected root organization.',
            self::PERMISSION_CATALOGUE => 'Synchronize the complete tenant permission catalogue.',
            self::SUPER_ADMIN_ACCESS => 'Assign the exact permission catalogue to the protected Super Admin role.',
            self::AUTHENTICATION_PROVIDER => 'Provision an active tenant authentication provider.',
            self::ADMINISTRATOR_INVITATION_ACCEPTED => 'The initial administrator must accept the invitation.',
            self::OPERATIONAL_ADMINISTRATOR => 'An active administrator with root organization and Super Admin access is required.',
            self::BASE_CURRENCY => 'Select an active base accounting currency.',
            self::ACTIVE_PLAN => 'Assign a revision from an active tenant plan.',
            self::SUBSCRIPTION_VALID => 'Assign a current, unexpired tenant subscription.',
            self::PRIMARY_DOMAIN_READY => 'The primary tenant domain must pass ownership, routing, TLS, and reachability checks.',
        ];
    }

    private function __construct() {}
}
