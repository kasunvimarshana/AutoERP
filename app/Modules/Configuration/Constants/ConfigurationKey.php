<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Constants;

final class ConfigurationKey
{
    public const WORKSPACE_TIMEZONE = 'localization.timezone';

    // Branding & Identity
    public const BRANDING_DISPLAY_NAME = 'branding.display_name';
    public const BRANDING_COMPANY_NAME = 'branding.company_name';
    public const BRANDING_SUPPORT_EMAIL = 'branding.support_email';
    public const BRANDING_SUPPORT_PHONE = 'branding.support_phone';

    // Localization & Presentation
    public const LOCALIZATION_TIMEZONE = 'localization.timezone';
    public const LOCALIZATION_DATE_FORMAT = 'localization.date_format';
    public const LOCALIZATION_TIME_FORMAT = 'localization.time_format';
    public const LOCALIZATION_CURRENCY = 'localization.currency';

    // Email & Mailer Settings
    public const MAIL_MAILER = 'mail.mailer';
    public const MAIL_HOST = 'mail.host';
    public const MAIL_PORT = 'mail.port';
    public const MAIL_USERNAME = 'mail.username';
    public const MAIL_PASSWORD = 'mail.password';
    public const MAIL_ENCRYPTION = 'mail.encryption';
    public const MAIL_FROM_ADDRESS = 'mail.from_address';
    public const MAIL_FROM_NAME = 'mail.from_name';

    // System Preferences & Operational Policies
    public const SYSTEM_SESSION_TIMEOUT_MINUTES = 'system.session_timeout_minutes';
    public const SYSTEM_PASSWORD_EXPIRY_DAYS = 'system.password_expiry_days';
    public const SYSTEM_MAINTENANCE_NOTICE = 'system.maintenance_notice';

    private function __construct() {}
}
