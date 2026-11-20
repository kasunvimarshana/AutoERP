<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceBalanceService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\IncidentStatus;
use Modules\VehicleRental\Enums\IncidentType;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\BillingCycleChart;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalExpenseService;
use Modules\VehicleRental\Services\RentalIncidentService;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\TwoStageInvoicingService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class VehicleRentalCrudAuditAndEdgeCasesTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    private const ROOT = '/api/v1/vehicle-rental';

    private function activate(string $kind, array $input): array
    {
        $path = self::ROOT.'/'.$kind.'/agreements';
        $draft = $this->postJson($path, $input)->assertCreated()->json('data');

        return $this->postJson($path.'/'.$draft['id'].'/activate', ['expected_version' => $draft['row_version']])->assertOk()->json('data');
    }

    public function test_adjustment_endpoints_require_permissions(): void
    {
        // 1. Unauthenticated user gets 401
        $this->getJson(self::ROOT.'/adjustments')
            ->assertUnauthorized();

        // 2. User without adjustment/customer permissions gets 403 Forbidden
        $this->loginFixture(permissions: [RentalAuthorization::DASHBOARD_VIEW]);

        $this->getJson(self::ROOT.'/adjustments')
            ->assertForbidden();

        $this->postJson(self::ROOT.'/adjustments', [
            'customer_agreement_id' => 1,
            'adjustment_type' => 'credit_note',
            'category' => 'commercial_rebate',
            'amount' => '1000.00',
            'reason' => 'Unauthorized attempt',
        ])->assertForbidden();
    }

    public function test_create_customer_agreement_adjustment_and_crud(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();
        $customerAg = $this->activate('customer', $customerData);
        $ownerAg = $this->activate('owner', $ownerData);

        // 1. Create Customer Adjustment (Debit Note)
        $res = $this->postJson(self::ROOT.'/adjustments', [
            'customer_agreement_id' => $customerAg['id'],
            'adjustment_type' => AgreementAdjustmentType::DebitNote->value,
            'category' => AgreementAdjustmentCategory::DamageChargeback->value,
            'amount' => '4500.00',
            'reason' => 'Mirror damage repair chargeback',
        ])->assertCreated();

        $adjId = $res->json('data.id');
        $this->assertSame(AgreementAdjustmentStatus::Authorized->value, $res->json('data.status'));
        $this->assertSame('ADJ-'.str_pad((string) $adjId, 5, '0', STR_PAD_LEFT), $res->json('data.reference'));
        $this->assertSame('Debit Note', $res->json('data.adjustment_type_label'));
        $this->assertSame('Damage Chargeback', $res->json('data.category_label'));
        $this->assertSame('4500.000000', $res->json('data.amount'));
        $this->assertSame($customerAg['reference'], $res->json('data.customer_agreement.reference'));

        // 2. Create Owner Adjustment (Credit Note)
        $ownerRes = $this->postJson(self::ROOT.'/adjustments', [
            'owner_agreement_id' => $ownerAg['id'],
            'adjustment_type' => AgreementAdjustmentType::CreditNote->value,
            'category' => AgreementAdjustmentCategory::DowntimeCompensation->value,
            'amount' => '2500.00',
            'reason' => 'Off-road downtime compensation rebate',
        ])->assertCreated();

        $ownerAdjId = $ownerRes->json('data.id');
        $this->assertSame('Credit Note', $ownerRes->json('data.adjustment_type_label'));
        $this->assertSame('Downtime Compensation', $ownerRes->json('data.category_label'));
        $this->assertSame($ownerAg['reference'], $ownerRes->json('data.owner_agreement.reference'));

        // 3. Index Customer Adjustments
        $listRes = $this->getJson(self::ROOT.'/adjustments?kind=customer&agreement_id='.$customerAg['id'])
            ->assertOk();
        $this->assertCount(1, $listRes->json('data'));
        $this->assertSame($adjId, $listRes->json('data.0.id'));

        // 4. Index Owner Adjustments
        $ownerListRes = $this->getJson(self::ROOT.'/adjustments?kind=owner&agreement_id='.$ownerAg['id'])
            ->assertOk();
        $this->assertCount(1, $ownerListRes->json('data'));
        $this->assertSame($ownerAdjId, $ownerListRes->json('data.0.id'));

        // 5. Show Detail
        $showRes = $this->getJson(self::ROOT.'/adjustments/'.$adjId)
            ->assertOk();
        $this->assertSame($adjId, $showRes->json('data.id'));
        $this->assertCount(1, $showRes->json('data.history'));

        // 6. Void Adjustment with stale version fails
        $this->postJson(self::ROOT.'/adjustments/'.$adjId.'/void', [
            'expected_version' => 99,
            'reason' => 'Mistaken entry',
        ])->assertStatus(409);

        // 7. Void Adjustment successfully
        $voidRes = $this->postJson(self::ROOT.'/adjustments/'.$adjId.'/void', [
            'expected_version' => 1,
            'reason' => 'Assessment recalculated, voiding old adjustment',
        ])->assertOk();
        $this->assertSame(AgreementAdjustmentStatus::Voided->value, $voidRes->json('data.status'));
        $this->assertSame(2, $voidRes->json('data.row_version'));
        $this->assertCount(2, $voidRes->json('data.history'));

        // 8. Re-voiding fails
        $this->postJson(self::ROOT.'/adjustments/'.$adjId.'/void', [
            'expected_version' => 2,
            'reason' => 'Voiding again',
        ])->assertStatus(409);

        // 9. Destroy is prohibited
        $this->deleteJson(self::ROOT.'/adjustments/'.$adjId)
            ->assertStatus(405);
    }

    public function test_adjustment_validation_rules_reject_invalid_inputs(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();
        $customerAg = $this->activate('customer', $customerData);
        $ownerAg = $this->activate('owner', $ownerData);

        // A. Missing amount -> 422
        $this->postJson(self::ROOT.'/adjustments', [
            'customer_agreement_id' => $customerAg['id'],
            'adjustment_type' => 'debit_note',
            'category' => 'damage_chargeback',
            'reason' => 'Valid reason',
        ])->assertStatus(422)->assertJsonValidationErrors(['amount']);

        // B. Missing reason -> 422
        $this->postJson(self::ROOT.'/adjustments', [
            'customer_agreement_id' => $customerAg['id'],
            'adjustment_type' => 'debit_note',
            'category' => 'damage_chargeback',
            'amount' => '100.00',
        ])->assertStatus(422)->assertJsonValidationErrors(['reason']);

        // C. Both agreements supplied -> 422 (prohibits)
        $this->postJson(self::ROOT.'/adjustments', [
            'customer_agreement_id' => $customerAg['id'],
            'owner_agreement_id' => $ownerAg['id'],
            'adjustment_type' => 'debit_note',
            'category' => 'damage_chargeback',
            'amount' => '100.00',
            'reason' => 'Valid reason',
        ])->assertStatus(422);

        // D. Neither agreement supplied -> 422 (required_without)
        $this->postJson(self::ROOT.'/adjustments', [
            'adjustment_type' => 'debit_note',
            'category' => 'damage_chargeback',
            'amount' => '100.00',
            'reason' => 'Valid reason',
        ])->assertStatus(422);
    }

    public function test_draft_billing_cycle_cancellation_and_date_reuse(): void
    {
        [$context, $customerData] = $this->loginFixture();
        $customerData['terms']['base_rate'] = '3000';
        $customerData['starts_on'] = '2026-09-01';
        $customerAg = $this->activate('customer', $customerData);

        // 1. Create a Draft billing cycle without initial invoice
        $cycleRes = $this->postJson(self::ROOT."/customer/agreements/{$customerAg['id']}/billing-cycles", [
            'period_from' => '2026-09-01',
            'period_until' => '2026-09-30',
            'issue_initial_invoice' => false,
            'billing_mode' => 'without_running_chart',
        ])->assertCreated();

        $cycleId = $cycleRes->json('data.id');
        $this->assertSame(BillingCycleStatus::Draft->value, $cycleRes->json('data.status'));

        // 2. Attempting to create an overlapping cycle fails
        $this->postJson(self::ROOT."/customer/agreements/{$customerAg['id']}/billing-cycles", [
            'period_from' => '2026-09-15',
            'period_until' => '2026-10-15',
            'issue_initial_invoice' => false,
        ])->assertStatus(422);

        // 3. Cancel the draft billing cycle
        $cancelRes = $this->postJson(self::ROOT."/billing-cycles/{$cycleId}/cancel", [
            'expected_version' => 1,
            'reason' => 'User chose incorrect cycle dates',
        ])->assertOk();

        $this->assertSame(BillingCycleStatus::Cancelled->value, $cancelRes->json('data.status'));
        $this->assertSame(2, $cancelRes->json('data.row_version'));

        // 4. Now creating a cycle covering the EXACT same dates succeeds!
        $newCycleRes = $this->postJson(self::ROOT."/customer/agreements/{$customerAg['id']}/billing-cycles", [
            'period_from' => '2026-09-01',
            'period_until' => '2026-09-30',
            'issue_initial_invoice' => false,
            'billing_mode' => 'without_running_chart',
        ])->assertCreated();

        $this->assertSame(BillingCycleStatus::Draft->value, $newCycleRes->json('data.status'));

        // 5. Delete cycle returns 405 MethodNotAllowed
        $this->deleteJson(self::ROOT."/billing-cycles/{$cycleId}")
            ->assertStatus(405);
    }

    public function test_running_chart_cross_cycle_finalization_isolation(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();
        $customerData['terms']['base_rate'] = '3000';
        $customerData['starts_on'] = '2026-09-01';
        $ownerData['terms']['base_rate'] = '2000';
        $ownerData['starts_on'] = '2026-09-01';

        $customerAg = $this->activate('customer', $customerData);
        $ownerAg = $this->activate('owner', $ownerData);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerAg, $ownerAg, $ownerData): void {
            // Create vehicle use
            $use = app(VehicleUseService::class)->plan($context, $customerAg['id'], $customerAg['row_version'], [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg['id'],
                'starts_at' => '2026-09-01T08:00:00+05:30',
                'ends_at' => '2026-09-30T18:00:00+05:30',
            ]);
            $use = app(VehicleUseService::class)->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+05:30',
                'odometer' => '10000',
                'reason' => 'Handed over to customer',
            ]);

            // Create and finalize a running chart
            $chart = app(RunningChartService::class)->create($context, $use->id, $use->row_version, [
                'reference' => 'RC-ISOLATION-01',
                'starts_at' => '2026-09-05T08:00:00+05:30',
                'ends_at' => '2026-09-05T17:00:00+05:30',
                'start_odometer' => '10000',
                'end_odometer' => '10150',
                'commercial_km' => '150.000000',
            ]);
            $chart = app(RunningChartService::class)->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);

            // Create customer cycle and attach chart
            $customerCycle = app(TwoStageInvoicingService::class)->createCycle(
                AgreementKind::Customer,
                $context,
                $customerAg['id'],
                [
                    'period_from' => '2026-09-01',
                    'period_until' => '2026-09-30',
                    'issue_initial_invoice' => false,
                ]
            );
            $customerCycle = app(TwoStageInvoicingService::class)->attachRunningCharts(
                $context,
                $customerCycle->id,
                [$chart->id],
                $customerCycle->row_version
            );

            // Finalize customer cycle
            $finalizedCustCycle = app(TwoStageInvoicingService::class)->finalize(
                $context,
                $customerCycle->id,
                ['invoice_date' => '2026-09-30'],
                $customerCycle->row_version
            );
            $this->assertSame(BillingCycleStatus::Finalized, $finalizedCustCycle->status);

            // Now create Owner cycle and verify chart #1 can be attached without being blocked by customer cycle finalization!
            $ownerCycle = app(TwoStageInvoicingService::class)->createCycle(
                AgreementKind::Owner,
                $context,
                $ownerAg['id'],
                [
                    'period_from' => '2026-09-01',
                    'period_until' => '2026-09-30',
                    'issue_initial_invoice' => false,
                ]
            );

            // Candidate query should show chart #1 is NOT finalized in another OWNER cycle
            $candidates = app(TwoStageInvoicingService::class)->getCandidateRunningCharts($context, $ownerCycle->id);
            $candidateChart = collect($candidates)->firstWhere('id', $chart->id);
            $this->assertNotNull($candidateChart);
            $this->assertFalse($candidateChart['is_already_finalized']);

            // Attaching to owner cycle succeeds!
            $attachedOwnerCycle = app(TwoStageInvoicingService::class)->attachRunningCharts(
                $context,
                $ownerCycle->id,
                [$chart->id],
                $ownerCycle->row_version
            );
            $this->assertCount(1, $attachedOwnerCycle->charts);
        });
    }

    public function test_rental_incident_filter_and_review_with_incident_request(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        // 1. Filter incidents with status and type query params without validation error
        $this->getJson(self::ROOT.'/incidents?status='.IncidentStatus::Recorded->value.'&incident_type='.IncidentType::Fuel->value)
            ->assertOk();

        // 2. Destroy incident returns 405 MethodNotAllowed
        $this->deleteJson(self::ROOT.'/incidents/1')
            ->assertStatus(405);
    }

    public function test_destructive_delete_operations_are_universally_prohibited_with_405(): void
    {
        [$context, $customerData] = $this->loginFixture();

        // 1. Agreement delete -> 405
        $this->deleteJson(self::ROOT.'/customer/agreements/1')->assertStatus(405);
        $this->deleteJson(self::ROOT.'/owner/agreements/1')->assertStatus(405);

        // 2. Billing cycle delete -> 405
        $this->deleteJson(self::ROOT.'/billing-cycles/1')->assertStatus(405);

        // 3. Adjustment delete -> 405
        $this->deleteJson(self::ROOT.'/adjustments/1')->assertStatus(405);

        // 4. Incident delete -> 405
        $this->deleteJson(self::ROOT.'/incidents/1')->assertStatus(405);

        // 5. Expense delete -> 405
        $this->deleteJson(self::ROOT.'/expenses/1')->assertStatus(405);
    }

    public function test_rental_expense_allocation_billing_lifecycle_and_status_transitions(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);
        $customerData['starts_on'] = '2026-09-01';
        $customerData['agreed_on'] = '2026-09-01';
        $customerData['terms']['base_rate'] = '30000.000000';
        $customerAg = $this->activate('customer', $customerData);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerAg, $ownerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);

            $service = app(RentalExpenseService::class);
            $expense = $service->create([
                'vehicle_id' => $ownerData['vehicle_id'],
                'expense_type' => RentalExpenseType::Fuel->value,
                'expense_date' => '2026-09-10',
                'currency_id' => $ownerData['currency_id'],
                'net_amount' => '5000.000000',
                'tax_amount' => '0.000000',
                'reference_number' => 'EXP-LIFECYCLE-1',
                'description' => 'Test operational fuel',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $submitted = $service->transition($expense, RentalExpenseStatus::Submitted, $expense->row_version, $context->actorId);
            $approved = $service->transition($submitted, RentalExpenseStatus::Approved, $submitted->row_version, $context->actorId);
            $allocated = $service->transition($approved, RentalExpenseStatus::Allocated, $approved->row_version, $context->actorId, [
                [
                    'allocation_type' => RentalExpenseAllocationType::CustomerRecovery->value,
                    'target_customer_agreement_id' => $customerAg['id'],
                    'net_amount' => '5000.000000',
                ],
            ]);

            $alloc = $allocated->allocations->first();
            $this->assertSame(RentalExpenseAllocationStatus::Confirmed, $alloc->status);
            $this->assertNull($alloc->invoice_id);

            // Bill allocation -> status becomes Invoiced
            $invoice = $service->billAllocation($alloc, $context, [
                'expected_allocation_version' => $alloc->row_version,
                'expected_agreement_version' => $customerAg['row_version'],
                'invoice_date' => '2026-09-19',
                'exchange_rate' => '1.000000',
            ]);

            $alloc->refresh();
            $this->assertSame(RentalExpenseAllocationStatus::Invoiced, $alloc->status);
            $this->assertSame($invoice->id, $alloc->invoice_id);

            // Cancel the invoice
            $inv = Invoice::query()->findOrFail($invoice->id);
            $cancelled = app(InvoiceStatusService::class)->transitionIfVersion(
                $inv,
                InvoiceStatus::Cancelled,
                $inv->row_version,
                $context->actorId,
                'Testing cancellation'
            );
            app(InvoiceBalanceService::class)->cancel($cancelled);

            // Unlink invoice -> status restored to Confirmed, invoice_id cleared
            $unlinked = $service->unlinkCancelledInvoice($alloc, $context);
            $this->assertSame(RentalExpenseAllocationStatus::Confirmed, $unlinked->status);
            $this->assertNull($unlinked->invoice_id);
        });
    }

    private function setUserPermissions($context, array $permissionNames): void
    {
        DB::table('user_permissions')->where('user_id', $context->actorId)->delete();
        foreach ($permissionNames as $name) {
            $permId = DB::table('permissions')->where('tenant_id', $context->tenantId)->where('name', $name)->value('id');
            if ($permId) {
                DB::table('user_permissions')->insert([
                    'tenant_id' => $context->tenantId,
                    'user_id' => $context->actorId,
                    'permission_id' => $permId,
                    'row_version' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        app(UserAccessResolver::class)->forgetForUserTenant($context->actorId, $context->tenantId);
    }

    public function test_rental_incident_request_validates_review_and_attachment_routes_properly(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();
        $customerData['starts_on'] = '2026-09-01';
        $ownerData['starts_on'] = '2026-09-01';
        $customerAg = $this->activate('customer', $customerData);
        $ownerAg = $this->activate('owner', $ownerData);

        $incident = $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerAg, $ownerAg, $ownerData) {
            $use = app(VehicleUseService::class)->plan($context, $customerAg['id'], $customerAg['row_version'], [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg['id'],
                'starts_at' => '2026-09-01T08:00:00+05:30',
                'ends_at' => '2026-09-30T18:00:00+05:30',
            ]);

            return app(RentalIncidentService::class)->create($context, [
                'vehicle_use_id' => $use->id,
                'expected_use_version' => $use->row_version,
                'incident_type' => IncidentType::AccidentDamage->value,
                'occurred_on' => '2026-09-05',
                'evidence_reference' => 'EVD-ROUTE-TEST',
                'description' => 'Test route validation',
            ]);
        });

        // Review without expected_version returns validation error on expected_version (NOT on vehicle_use_id!)
        $this->postJson(self::ROOT."/incidents/{$incident->id}/confirm", [
            'reason' => 'Confirmed without version',
        ])->assertStatus(422)->assertJsonValidationErrors(['expected_version']);

        // Attachment without expected_version and file returns validation error on expected_version and file
        $this->postJson(self::ROOT."/incidents/{$incident->id}/attachments", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['expected_version', 'file']);
    }

    public function test_billing_cycle_cannot_be_finalized_or_previewed_when_cancelled(): void
    {
        [$context, $customerData] = $this->loginFixture();
        $customerData['terms']['base_rate'] = '3000';
        $customerData['starts_on'] = '2026-09-01';
        $customerAg = $this->activate('customer', $customerData);

        // 1. Create a Draft billing cycle
        $cycleRes = $this->postJson(self::ROOT."/customer/agreements/{$customerAg['id']}/billing-cycles", [
            'period_from' => '2026-09-01',
            'period_until' => '2026-09-30',
            'issue_initial_invoice' => false,
            'billing_mode' => 'without_running_chart',
        ])->assertCreated();

        $cycleId = $cycleRes->json('data.id');

        // 2. Cancel the billing cycle
        $this->postJson(self::ROOT."/billing-cycles/{$cycleId}/cancel", [
            'expected_version' => 1,
            'reason' => 'Testing cancelled finalization protection',
        ])->assertOk();

        // 3. Attempting to finalize cancelled cycle throws validation exception
        $this->postJson(self::ROOT."/billing-cycles/{$cycleId}/finalize", [
            'expected_version' => 2,
            'invoice_date' => '2026-09-30',
        ])->assertStatus(422)->assertJsonValidationErrors(['cycle']);

        // 4. Attempting to preview final stage on cancelled cycle throws validation exception
        $this->getJson(self::ROOT."/billing-cycles/{$cycleId}/preview")
            ->assertStatus(422)->assertJsonValidationErrors(['cycle']);
    }

    public function test_finalize_billing_cycle_prevents_double_billing_of_already_finalized_running_charts(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();
        $customerData['terms']['base_rate'] = '3000';
        $customerData['starts_on'] = '2026-09-01';
        $ownerData['terms']['base_rate'] = '2000';
        $ownerData['starts_on'] = '2026-09-01';
        $customerAg = $this->activate('customer', $customerData);
        $ownerAg = $this->activate('owner', $ownerData);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerAg, $ownerAg, $ownerData): void {
            $use = app(VehicleUseService::class)->plan($context, $customerAg['id'], $customerAg['row_version'], [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg['id'],
                'starts_at' => '2026-09-01T08:00:00+05:30',
                'ends_at' => '2026-09-30T18:00:00+05:30',
            ]);
            $use = app(VehicleUseService::class)->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+05:30',
                'odometer' => '10000',
                'reason' => 'Handed over',
            ]);

            $chart = app(RunningChartService::class)->create($context, $use->id, $use->row_version, [
                'reference' => 'RC-DOUBLE-BILL-01',
                'starts_at' => '2026-09-05T08:00:00+05:30',
                'ends_at' => '2026-09-05T17:00:00+05:30',
                'start_odometer' => '10000',
                'end_odometer' => '10100',
                'commercial_km' => '100.000000',
            ]);
            $chart = app(RunningChartService::class)->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);

            $service = app(TwoStageInvoicingService::class);
            $cycle1 = $service->createCycle(AgreementKind::Customer, $context, $customerAg['id'], [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-15',
                'issue_initial_invoice' => false,
            ]);
            $cycle1 = $service->attachRunningCharts($context, $cycle1->id, [$chart->id], $cycle1->row_version);

            $cycle2 = $service->createCycle(AgreementKind::Customer, $context, $customerAg['id'], [
                'period_from' => '2026-09-16',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => false,
            ]);

            // Simulate race / manual attachment of chart into cycle 2
            BillingCycleChart::forceCreate([
                'tenant_id' => $context->tenantId,
                'billing_cycle_id' => $cycle2->id,
                'running_chart_id' => $chart->id,
                'vehicle_id' => $ownerData['vehicle_id'],
                'starts_at' => '2026-09-05 08:00:00',
                'ends_at' => '2026-09-05 17:00:00',
                'commercial_km' => '100.000000',
                'normal_ot_minutes' => 0,
                'double_ot_minutes' => 0,
                'triple_ot_minutes' => 0,
                'night_outs' => 0,
            ]);

            // Finalize cycle 1 -> succeeds
            $cycle1 = $service->finalize($context, $cycle1->id, ['invoice_date' => '2026-09-15'], $cycle1->row_version);
            $this->assertSame(BillingCycleStatus::Finalized, $cycle1->status);

            // Finalize cycle 2 -> rejected because chart was already finalized in cycle 1
            $this->expectException(ValidationException::class);
            $service->finalize($context, $cycle2->id, ['invoice_date' => '2026-09-30'], $cycle2->row_version);
        });
    }

    public function test_agreement_controller_create_financial_adjustment_requires_billing_permission(): void
    {
        [$context, $customerData] = $this->loginFixture(modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);
        $customerAg = $this->activate('customer', $customerData);

        // Revoke billing and manage permissions, leave only customer view
        $this->setUserPermissions($context, [RentalAuthorization::CUSTOMER_VIEW]);

        // Attempt to create financial adjustment with only view permission gets 403 Forbidden
        $this->postJson(self::ROOT."/customer/agreements/{$customerAg['id']}/financial-adjustments", [
            'expected_version' => $customerAg['row_version'],
            'adjustment_type' => 'credit_note',
            'amount' => '1000.000000',
            'reason' => 'Unauthorized financial adjustment attempt',
            'invoice_date' => '2026-09-12',
            'exchange_rate' => '1.000000',
        ])->assertForbidden();
    }

    public function test_billing_cycle_controller_index_and_show_enforce_view_permissions(): void
    {
        [$context, $customerData] = $this->loginFixture();
        $customerData['terms']['base_rate'] = '3000';
        $customerData['starts_on'] = '2026-09-01';
        $customerAg = $this->activate('customer', $customerData);

        $cycleRes = $this->postJson(self::ROOT."/customer/agreements/{$customerAg['id']}/billing-cycles", [
            'period_from' => '2026-09-01',
            'period_until' => '2026-09-30',
            'issue_initial_invoice' => false,
            'billing_mode' => 'without_running_chart',
        ])->assertCreated();
        $cycleId = $cycleRes->json('data.id');

        // Drop permissions to dashboard view only (no customer view / billing permissions)
        $this->setUserPermissions($context, [RentalAuthorization::DASHBOARD_VIEW]);

        $this->getJson(self::ROOT.'/billing-cycles')
            ->assertForbidden();

        $this->getJson(self::ROOT."/billing-cycles/{$cycleId}")
            ->assertForbidden();
    }

    public function test_void_draft_agreement_adjustment(): void
    {
        [$context, $customerData] = $this->loginFixture();
        $customerAg = $this->activate('customer', $customerData);

        $adj = $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerAg) {
            $record = new AgreementAdjustment;
            $record->tenant_id = $context->tenantId;
            $record->organization_unit_id = $context->organizationUnitId;
            $record->customer_agreement_id = $customerAg['id'];
            $record->adjustment_type = AgreementAdjustmentType::CreditNote;
            $record->category = AgreementAdjustmentCategory::CommercialRebate;
            $record->status = AgreementAdjustmentStatus::Draft;
            $record->amount = '1200.000000';
            $record->original_amount = '1200.000000';
            $record->tax_amount = '0.000000';
            $record->reason = 'Draft adjustment for review';
            $record->actor_id = $context->actorId;
            $record->row_version = 1;
            $record->save();

            return $record;
        });

        // Voiding draft adjustment succeeds
        $res = $this->postJson(self::ROOT."/adjustments/{$adj->id}/void", [
            'expected_version' => 1,
            'reason' => 'Voiding draft adjustment prior to authorization',
        ])->assertOk();

        $this->assertSame(AgreementAdjustmentStatus::Voided->value, $res->json('data.status'));
    }

    public function test_billing_cycle_finalize_endpoint_rejects_due_date_before_invoice_date(): void
    {
        [$context, $customerData] = $this->loginFixture(modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);
        $customerData['starts_on'] = '2026-09-01';
        $customerData['terms']['base_rate'] = '3000';
        $customerAg = $this->activate('customer', $customerData);

        $cycleRes = $this->postJson(self::ROOT."/customer/agreements/{$customerAg['id']}/billing-cycles", [
            'period_from' => '2026-09-01',
            'period_until' => '2026-09-30',
            'issue_initial_invoice' => false,
        ])->assertCreated()->json('data');

        // Finalize with due_date before invoice_date
        $res = $this->postJson(self::ROOT."/billing-cycles/{$cycleRes['id']}/finalize", [
            'expected_version' => $cycleRes['row_version'],
            'invoice_date' => '2026-09-30',
            'due_date' => '2026-09-15',
        ]);

        $res->assertUnprocessable();
        $res->assertJsonValidationErrors(['due_date']);
    }

    public function test_owner_agreement_vehicles_and_running_chart_register_filtering(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);
        $customerAg = $this->activate('customer', $customerData);
        $ownerAg = $this->activate('owner', $ownerData);

        // Assign vehicle to customer with owner agreement as source
        $use = $this->postJson(self::ROOT."/customer/agreements/{$customerAg['id']}/vehicles", [
            'vehicle_id' => $ownerData['vehicle_id'],
            'owner_agreement_id' => $ownerAg['id'],
            'starts_at' => '2026-09-07T09:00:00+05:30',
            'ends_at' => '2026-09-10T18:00:00+05:30',
            'expected_version' => $customerAg['row_version'],
        ])->assertCreated()->json('data');

        // Check owner agreement vehicle endpoint
        $ownerVehicles = $this->getJson(self::ROOT."/owner/agreements/{$ownerAg['id']}/vehicles")->assertOk()->json('data');
        $this->assertCount(1, $ownerVehicles);
        $this->assertSame($use['id'], $ownerVehicles[0]['id']);

        // Check generic {kind}/agreements/{agreement}/vehicles endpoint
        $ownerVehiclesGeneric = $this->getJson(self::ROOT."/owner/agreements/{$ownerAg['id']}/vehicles")->assertOk()->json('data');
        $this->assertCount(1, $ownerVehiclesGeneric);

        // Record a running chart on this use
        $chart = $this->postJson(self::ROOT."/vehicle-uses/{$use['id']}/running-charts", [
            'reference' => 'RC-OWNER-TEST-01',
            'starts_at' => '2026-09-07T10:00:00+05:30',
            'ends_at' => '2026-09-07T12:00:00+05:30',
            'start_odometer' => '1000.000000',
            'end_odometer' => '1150.000000',
            'expected_version' => $use['row_version'],
        ])->assertCreated()->json('data');

        // Verify running chart register filters by owner_agreement_id, customer_agreement_id, vehicle_use_id, kind
        $byOwner = $this->getJson(self::ROOT."/running-charts?owner_agreement_id={$ownerAg['id']}")->assertOk()->json('data');
        $this->assertCount(1, $byOwner);
        $this->assertSame($chart['id'], $byOwner[0]['id']);
        $this->assertSame($ownerAg['id'], $byOwner[0]['owner_agreement']['id']);
        $this->assertSame($customerAg['id'], $byOwner[0]['customer_agreement']['id']);

        $byCustomer = $this->getJson(self::ROOT."/running-charts?customer_agreement_id={$customerAg['id']}")->assertOk()->json('data');
        $this->assertCount(1, $byCustomer);

        $byUse = $this->getJson(self::ROOT."/running-charts?vehicle_use_id={$use['id']}")->assertOk()->json('data');
        $this->assertCount(1, $byUse);

        $byKindOwner = $this->getJson(self::ROOT.'/running-charts?kind=owner')->assertOk()->json('data');
        $this->assertCount(1, $byKindOwner);

        $byKindCustomer = $this->getJson(self::ROOT.'/running-charts?kind=customer')->assertOk()->json('data');
        $this->assertCount(1, $byKindCustomer);

        $byKindInternal = $this->getJson(self::ROOT.'/running-charts?kind=internal')->assertOk()->json('data');
        $this->assertCount(0, $byKindInternal);
    }
}
