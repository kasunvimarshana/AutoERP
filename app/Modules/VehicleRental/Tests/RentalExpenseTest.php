<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Services\RentalExpenseService;
use Tests\TestCase;

final class RentalExpenseTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_rental_expense_workflow_and_allocations(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $service = app(RentalExpenseService::class);

            $expense = $service->create([
                'vehicle_id' => $ownerData['vehicle_id'],
                'expense_type' => RentalExpenseType::Fuel->value,
                'expense_date' => '2026-09-10',
                'currency_id' => $ownerData['currency_id'],
                'net_amount' => '10000.000000',
                'tax_amount' => '0.000000',
                'reference_number' => 'FUEL-001',
                'description' => 'Operational fuel fillup',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $this->assertSame(RentalExpenseStatus::Draft, $expense->status);
            $this->assertSame('10000.000000', (string) $expense->gross_amount);

            // Submit
            $submitted = $service->transition($expense, RentalExpenseStatus::Submitted, $expense->row_version, $context->actorId);
            $this->assertSame(RentalExpenseStatus::Submitted, $submitted->status);

            // Approve
            $approved = $service->transition($submitted, RentalExpenseStatus::Approved, $submitted->row_version, $context->actorId);
            $this->assertSame(RentalExpenseStatus::Approved, $approved->status);

            // Allocate
            $allocated = $service->transition($approved, RentalExpenseStatus::Allocated, $approved->row_version, $context->actorId, [
                [
                    'allocation_type' => RentalExpenseAllocationType::CustomerRecovery->value,
                    'customer_id' => $customerData['party_id'],
                    'net_amount' => '6000.000000',
                    'tax_amount' => '0.000000',
                ],
                [
                    'allocation_type' => RentalExpenseAllocationType::CompanyCost->value,
                    'net_amount' => '4000.000000',
                    'tax_amount' => '0.000000',
                ],
            ]);

            $this->assertSame(RentalExpenseStatus::Allocated, $allocated->status);
            $this->assertCount(2, $allocated->allocations);
        });
    }

    public function test_duplicate_expense_fingerprint_rejected(): void
    {
        [$context, , $ownerData] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $service = app(RentalExpenseService::class);

            $payload = [
                'vehicle_id' => $ownerData['vehicle_id'],
                'expense_type' => RentalExpenseType::Toll->value,
                'expense_date' => '2026-09-10',
                'currency_id' => $ownerData['currency_id'],
                'net_amount' => '1500.000000',
                'reference_number' => 'TOLL-EXACT',
            ];

            $service->create($payload, $context->tenantId, $context->organizationUnitId, $context->actorId);

            $this->expectException(InvalidArgumentException::class);
            $service->create($payload, $context->tenantId, $context->organizationUnitId, $context->actorId);
        });
    }
}
