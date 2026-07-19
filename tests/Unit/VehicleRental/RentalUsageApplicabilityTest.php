<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalUsageEventApplicability;
use Modules\VehicleRental\Enums\RentalUsageFactStatus;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use PHPUnit\Framework\TestCase;

final class RentalUsageApplicabilityTest extends TestCase
{
    public function test_events_apply_only_to_their_commercial_side(): void
    {
        self::assertTrue(RentalUsageEventApplicability::Customer->appliesTo(RentalFinancialSide::Revenue));
        self::assertFalse(RentalUsageEventApplicability::Customer->appliesTo(RentalFinancialSide::Cost));
        self::assertFalse(RentalUsageEventApplicability::Owner->appliesTo(RentalFinancialSide::Revenue));
        self::assertTrue(RentalUsageEventApplicability::Owner->appliesTo(RentalFinancialSide::Cost));
        self::assertTrue(RentalUsageEventApplicability::Both->appliesTo(RentalFinancialSide::Revenue));
        self::assertTrue(RentalUsageEventApplicability::Both->appliesTo(RentalFinancialSide::Cost));
        self::assertFalse(RentalUsageEventApplicability::Internal->appliesTo(RentalFinancialSide::Revenue));
        self::assertFalse(RentalUsageEventApplicability::Internal->appliesTo(RentalFinancialSide::Cost));
    }

    public function test_operational_and_commercial_lifecycles_do_not_use_consumed_status(): void
    {
        $expected = ['draft', 'submitted', 'approved', 'rejected', 'reversed'];

        self::assertSame($expected, array_column(RentalUsageStatus::cases(), 'value'));
        self::assertSame($expected, array_column(RentalUsageFactStatus::cases(), 'value'));
        self::assertNotContains('consumed', array_column(RentalUsageStatus::cases(), 'value'));
    }
}
