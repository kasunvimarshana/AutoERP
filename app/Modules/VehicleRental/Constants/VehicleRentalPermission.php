<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

final class VehicleRentalPermission
{
    public const AGREEMENTS_VIEW = 'vehicle_rental.agreements.view';
    public const AGREEMENTS_MANAGE = 'vehicle_rental.agreements.manage';
    public const ASSIGNMENTS_VIEW = 'vehicle_rental.assignments.view';
    public const ASSIGNMENTS_MANAGE = 'vehicle_rental.assignments.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::AGREEMENTS_VIEW => 'View Vehicle Rental customer and owner agreements.',
            self::AGREEMENTS_MANAGE => 'Create, update, activate, and close Vehicle Rental agreements and rates.',
            self::ASSIGNMENTS_VIEW => 'View Vehicle Rental vehicle assignments and custody history.',
            self::ASSIGNMENTS_MANAGE => 'Assign, hand over, return, cancel, and replace rental vehicles.',
        ];
    }

    private function __construct() {}
}
