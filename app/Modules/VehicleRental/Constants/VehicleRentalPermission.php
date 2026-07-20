<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

final class VehicleRentalPermission
{
    public const AGREEMENTS_VIEW = 'vehicle_rental.agreements.view';
    public const AGREEMENTS_MANAGE = 'vehicle_rental.agreements.manage';
    public const ASSIGNMENTS_VIEW = 'vehicle_rental.assignments.view';
    public const ASSIGNMENTS_MANAGE = 'vehicle_rental.assignments.manage';
    public const RUNNING_CHARTS_VIEW = 'vehicle_rental.running_charts.view';
    public const RUNNING_CHARTS_MANAGE = 'vehicle_rental.running_charts.manage';
    public const CALCULATIONS_VIEW = 'vehicle_rental.calculations.view';
    public const CALCULATIONS_MANAGE = 'vehicle_rental.calculations.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::AGREEMENTS_VIEW => 'View Vehicle Rental customer and owner agreements.',
            self::AGREEMENTS_MANAGE => 'Create, update, activate, and close Vehicle Rental agreements and rates.',
            self::ASSIGNMENTS_VIEW => 'View Vehicle Rental vehicle assignments and custody history.',
            self::ASSIGNMENTS_MANAGE => 'Assign, hand over, return, cancel, and replace rental vehicles.',
            self::RUNNING_CHARTS_VIEW => 'View Vehicle Rental daily running charts and operational evidence.',
            self::RUNNING_CHARTS_MANAGE => 'Create, update, finalize, and reverse Vehicle Rental running charts.',
            self::CALCULATIONS_VIEW => 'View immutable Vehicle Rental customer and owner calculation snapshots.',
            self::CALCULATIONS_MANAGE => 'Create and cancel Vehicle Rental customer and owner calculations.',
        ];
    }

    private function __construct() {}
}
