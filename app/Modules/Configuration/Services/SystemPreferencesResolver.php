<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationKey;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Configuration\Contracts\SystemPreferencesResolverInterface;
use Modules\Configuration\Data\SystemPreferences;

final class SystemPreferencesResolver implements SystemPreferencesResolverInterface
{
    public function __construct(
        private readonly ConfigurationResolverInterface $resolver,
    ) {}

    public function resolve(int $tenantId, ?int $organizationUnitId = null): SystemPreferences
    {
        $displayName = (string) $this->resolver->value(ConfigurationKey::BRANDING_DISPLAY_NAME, $tenantId, $organizationUnitId);
        $companyName = (string) $this->resolver->value(ConfigurationKey::BRANDING_COMPANY_NAME, $tenantId, $organizationUnitId);
        $supportEmail = (string) $this->resolver->value(ConfigurationKey::BRANDING_SUPPORT_EMAIL, $tenantId, $organizationUnitId);
        $supportPhone = $this->resolver->value(ConfigurationKey::BRANDING_SUPPORT_PHONE, $tenantId, $organizationUnitId);
        $timezone = (string) $this->resolver->value(ConfigurationKey::LOCALIZATION_TIMEZONE, $tenantId, $organizationUnitId);
        $dateFormat = (string) $this->resolver->value(ConfigurationKey::LOCALIZATION_DATE_FORMAT, $tenantId, $organizationUnitId);
        $timeFormat = (string) $this->resolver->value(ConfigurationKey::LOCALIZATION_TIME_FORMAT, $tenantId, $organizationUnitId);
        $currency = (string) $this->resolver->value(ConfigurationKey::LOCALIZATION_CURRENCY, $tenantId, $organizationUnitId);
        $sessionTimeout = (int) $this->resolver->value(ConfigurationKey::SYSTEM_SESSION_TIMEOUT_MINUTES, $tenantId, $organizationUnitId);
        $passwordExpiry = (int) $this->resolver->value(ConfigurationKey::SYSTEM_PASSWORD_EXPIRY_DAYS, $tenantId, $organizationUnitId);
        $maintenanceNotice = $this->resolver->value(ConfigurationKey::SYSTEM_MAINTENANCE_NOTICE, $tenantId, $organizationUnitId);

        return new SystemPreferences(
            displayName: $displayName,
            companyName: $companyName,
            supportEmail: $supportEmail,
            supportPhone: $supportPhone !== null ? (string) $supportPhone : null,
            timezone: $timezone,
            dateFormat: $dateFormat,
            timeFormat: $timeFormat,
            currency: $currency,
            sessionTimeoutMinutes: $sessionTimeout,
            passwordExpiryDays: $passwordExpiry,
            maintenanceNotice: $maintenanceNotice !== null ? (string) $maintenanceNotice : null,
        );
    }
}
