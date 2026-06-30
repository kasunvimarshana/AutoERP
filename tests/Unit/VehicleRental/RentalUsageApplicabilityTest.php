<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalUsageEventApplicability;
use PHPUnit\Framework\TestCase;

final class RentalUsageApplicabilityTest extends TestCase
{
    public function test_events_apply_only_to_their_commercial_side(): void
    {
        self::assertTrue(RentalUsageEventApplicability::Customer->appliesTo(RentalFinancialSide::Revenue));
        self::assertFalse(RentalUsageEventApplicability::Customer->appliesTo(RentalFinancialSide::Cost));
        self::assertTrue(RentalUsageEventApplicability::Owner->appliesTo(RentalFinancialSide::Cost));
        self::assertFalse(RentalUsageEventApplicability::Internal->appliesTo(RentalFinancialSide::Revenue));
        self::assertFalse(RentalUsageEventApplicability::Internal->appliesTo(RentalFinancialSide::Cost));
    }
}
