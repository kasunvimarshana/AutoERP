<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantOnboardingStep
{
    public const ROOT_ORGANIZATION = 'root_organization';
    public const PERMISSION_CATALOGUE = 'permission_catalogue';
    public const SUPER_ADMIN_ROLE = 'super_admin_role';
    public const AUTHENTICATION_PROVIDER = 'authentication_provider';
    public const INITIAL_ADMIN_ACCOUNT = 'initial_admin_account';

    /** @return list<string> */
    public static function ordered(): array
    {
        return [
            self::ROOT_ORGANIZATION,
            self::PERMISSION_CATALOGUE,
            self::SUPER_ADMIN_ROLE,
            self::AUTHENTICATION_PROVIDER,
            self::INITIAL_ADMIN_ACCOUNT,
        ];
    }

    public static function owner(string $step): string
    {
        return match ($step) {
            self::ROOT_ORGANIZATION => 'OrganizationUnit',
            self::PERMISSION_CATALOGUE, self::SUPER_ADMIN_ROLE => 'User',
            self::AUTHENTICATION_PROVIDER => 'Auth',
            self::INITIAL_ADMIN_ACCOUNT => 'User',
            default => throw new \InvalidArgumentException("Unknown tenant onboarding step [{$step}]."),
        };
    }

    private function __construct() {}
}
