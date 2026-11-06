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

    public function test_rental_expense_allocates_to_customer_and_owner_agreements(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(\Modules\VehicleRental\Services\RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $agreements = app(\Modules\VehicleRental\Services\AgreementService::class);
            $customerAg = $agreements->create(\Modules\VehicleRental\Enums\AgreementKind::Customer, $context, $customerData);
            $ownerAg = $agreements->create(\Modules\VehicleRental\Enums\AgreementKind::Owner, $context, $ownerData);

            $service = app(RentalExpenseService::class);
            $expense = $service->create([
                'vehicle_id' => $ownerData['vehicle_id'],
                'expense_type' => RentalExpenseType::Toll->value,
                'expense_date' => '2026-09-15',
                'currency_id' => $ownerData['currency_id'],
                'net_amount' => '5000.000000',
                'reference_number' => 'EX-AG-ALLOC',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $submitted = $service->transition($expense, RentalExpenseStatus::Submitted, $expense->row_version, $context->actorId);
            $approved = $service->transition($submitted, RentalExpenseStatus::Approved, $submitted->row_version, $context->actorId);

            $allocated = $service->transition($approved, RentalExpenseStatus::Allocated, $approved->row_version, $context->actorId, [
                [
                    'allocation_type' => RentalExpenseAllocationType::CustomerRecovery->value,
                    'target_customer_agreement_id' => $customerAg->id,
                    'net_amount' => '3000.000000',
                ],
                [
                    'allocation_type' => RentalExpenseAllocationType::OwnerDeduction->value,
                    'target_owner_agreement_id' => $ownerAg->id,
                    'net_amount' => '2000.000000',
                ],
            ]);

            $this->assertSame(RentalExpenseStatus::Allocated, $allocated->status);
            $this->assertCount(2, $allocated->allocations);

            $alloc1 = $allocated->allocations[0];
            $this->assertSame($customerAg->id, $alloc1->target_customer_agreement_id);
            $this->assertSame($customerAg->customer_id, $alloc1->customer_id);

            $alloc2 = $allocated->allocations[1];
            $this->assertSame($ownerAg->id, $alloc2->target_owner_agreement_id);
            $this->assertSame($ownerAg->supplier_id, $alloc2->supplier_id);
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

    public function test_rental_expense_api_endpoints(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        // 1. Index expenses returns 200 without requiring POST fields
        $this->getJson($this->rentalRoot.'/expenses')
            ->assertOk()
            ->assertJsonPath('data', []);

        // 2. Store expense
        $createResponse = $this->postJson($this->rentalRoot.'/expenses', [
            'vehicle_id' => $ownerData['vehicle_id'],
            'expense_type' => RentalExpenseType::Maintenance->value,
            'expense_date' => '2026-09-12',
            'currency_id' => $ownerData['currency_id'],
            'net_amount' => '8000.000000',
            'reference_number' => 'MAINT-001',
            'description' => 'Regular service',
        ])->assertCreated();

        $expenseId = $createResponse->json('data.id');
        $version = $createResponse->json('data.row_version');

        // 3. Show expense
        $this->getJson($this->rentalRoot.'/expenses/'.$expenseId)
            ->assertOk()
            ->assertJsonPath('data.id', $expenseId)
            ->assertJsonPath('data.status', 'draft');

        // 4. Transition to submitted
        $submittedResponse = $this->patchJson($this->rentalRoot.'/expenses/'.$expenseId.'/transition', [
            'expected_version' => $version,
            'status' => RentalExpenseStatus::Submitted->value,
        ])->assertOk();

        $version = $submittedResponse->json('data.row_version');

        // 5. Transition to approved
        $approvedResponse = $this->patchJson($this->rentalRoot.'/expenses/'.$expenseId.'/transition', [
            'expected_version' => $version,
            'status' => RentalExpenseStatus::Approved->value,
        ])->assertOk();

        $version = $approvedResponse->json('data.row_version');

        // 6. Transition to allocated
        $allocatedResponse = $this->patchJson($this->rentalRoot.'/expenses/'.$expenseId.'/transition', [
            'expected_version' => $version,
            'status' => RentalExpenseStatus::Allocated->value,
            'allocations' => [
                [
                    'allocation_type' => RentalExpenseAllocationType::CompanyCost->value,
                    'net_amount' => '8000.000000',
                ],
            ],
        ])->assertOk();

        $this->assertSame('allocated', $allocatedResponse->json('data.status'));
        $this->assertCount(1, $allocatedResponse->json('data.allocations'));
    }

    public function test_expense_handles_empty_optional_fields_and_query_filters(): void
    {
        [$context, , $ownerData] = $this->loginFixture();

        // 1. Create expense with empty string optional fields
        $createResponse = $this->postJson($this->rentalRoot . '/expenses', [
            'vehicle_id' => $ownerData['vehicle_id'],
            'expense_type' => RentalExpenseType::Fuel->value,
            'expense_date' => '2026-09-15',
            'currency_id' => $ownerData['currency_id'],
            'net_amount' => '5000.000000',
            'expense_number' => '',
            'customer_agreement_id' => '',
            'vehicle_use_id' => '',
            'running_chart_id' => '',
            'supplier_id' => '',
            'employee_id' => '',
            'tax_group_id' => '',
            'tax_amount' => '',
            'reference_number' => '',
            'description' => '',
        ])->assertCreated();

        $expenseId = $createResponse->json('data.id');
        $version = $createResponse->json('data.row_version');

        $this->assertNotEmpty($createResponse->json('data.expense_number'));
        $this->assertSame('0.000000', (string) $createResponse->json('data.tax_amount'));
        $this->assertSame('5000.000000', (string) $createResponse->json('data.gross_amount'));
        $this->assertNull($createResponse->json('data.reference_number'));
        $this->assertNull($createResponse->json('data.description'));

        // 2. Query index with empty filter parameters
        $this->getJson($this->rentalRoot . '/expenses?search=&expense_type=&vehicle_id=&status=&from=&until=')
            ->assertOk();

        // 3. Transition to submitted then approved
        $submitted = $this->patchJson($this->rentalRoot . '/expenses/' . $expenseId . '/transition', [
            'expected_version' => $version,
            'status' => RentalExpenseStatus::Submitted->value,
        ])->assertOk();

        $approved = $this->patchJson($this->rentalRoot . '/expenses/' . $expenseId . '/transition', [
            'expected_version' => $submitted->json('data.row_version'),
            'status' => RentalExpenseStatus::Approved->value,
        ])->assertOk();

        // 4. Transition to allocated with empty optional allocation fields
        $allocated = $this->patchJson($this->rentalRoot . '/expenses/' . $expenseId . '/transition', [
            'expected_version' => $approved->json('data.row_version'),
            'status' => RentalExpenseStatus::Allocated->value,
            'allocations' => [
                [
                    'allocation_type' => RentalExpenseAllocationType::CompanyCost->value,
                    'net_amount' => '5000.000000',
                    'tax_amount' => '',
                    'markup_amount' => '',
                    'withholding_amount' => '',
                    'target_customer_agreement_id' => '',
                    'customer_id' => '',
                    'supplier_id' => '',
                    'employee_id' => '',
                    'notes' => '',
                ],
            ],
        ])->assertOk();

        $this->assertSame('allocated', $allocated->json('data.status'));
        $this->assertCount(1, $allocated->json('data.allocations'));
        $alloc = $allocated->json('data.allocations.0');
        $this->assertSame('0.000000', (string) $alloc['tax_amount']);
        $this->assertSame('5000.000000', (string) $alloc['total_amount']);
    }
}
