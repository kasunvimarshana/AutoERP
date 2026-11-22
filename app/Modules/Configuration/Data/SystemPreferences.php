<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Data;

final readonly class SystemPreferences
{
    public function __construct(
        public string $displayName,
        public string $companyName,
        public string $supportEmail,
        public ?string $supportPhone,
        public string $timezone,
        public string $dateFormat,
        public string $timeFormat,
        public string $currency,
        public int $sessionTimeoutMinutes,
        public int $passwordExpiryDays,
        public ?string $maintenanceNotice,
    ) {}
}
