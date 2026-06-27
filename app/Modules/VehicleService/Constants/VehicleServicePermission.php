<?php

declare(strict_types=1);

namespace Modules\VehicleService\Constants;

final class VehicleServicePermission
{
    public const VIEW = 'vehicle-service.view';
    public const MANAGE = 'vehicle-service.manage';
    public const INVOICE = 'vehicle-service.invoice';
    public const PAYMENT = 'vehicle-service.payment';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View vehicle-service jobs, work, documents, and history.',
            self::MANAGE => 'Manage vehicle-service jobs, inspections, work lines, workforce, inventory, and documents.',
            self::INVOICE => 'Prepare and create vehicle-service invoices.',
            self::PAYMENT => 'Prepare and create vehicle-service payments.',
        ];
    }
}
