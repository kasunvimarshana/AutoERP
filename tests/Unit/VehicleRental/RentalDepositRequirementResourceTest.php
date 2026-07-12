<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Illuminate\Http\Request;
use Modules\VehicleRental\Enums\RentalDepositStatus;
use Modules\VehicleRental\Http\Resources\RentalDepositRequirementResource;
use Modules\VehicleRental\Models\RentalDepositRequirement;
use Tests\TestCase;

final class RentalDepositRequirementResourceTest extends TestCase
{
    public function test_resource_exposes_the_authoritative_forfeited_amount(): void
    {
        $requirement = new RentalDepositRequirement();
        $requirement->forceFill([
            'id' => 41,
            'row_version' => 7,
            'required_amount' => '100000.000000',
            'received_amount' => '100000.000000',
            'applied_amount' => '25000.000000',
            'refunded_amount' => '15000.000000',
            'forfeited_amount' => '10000.000000',
            'balance_amount' => '50000.000000',
            'status' => RentalDepositStatus::PartiallyApplied->value,
            'is_refundable' => true,
        ]);

        $payload = (new RentalDepositRequirementResource($requirement))
            ->toArray(Request::create('/api/v1/vehicle-rental/deposits/41'));

        self::assertSame('10000.000000', $payload['forfeited_amount']);
        self::assertSame('50000.000000', $payload['balance_amount']);
    }
}
