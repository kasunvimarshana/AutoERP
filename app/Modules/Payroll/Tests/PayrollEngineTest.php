<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\DTOs\HrDepartmentData;
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\LeaveDurationType;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrLeaveType;
use Modules\Hr\Services\EmployeeCreationService;
use Modules\Hr\Services\HrDepartmentService;
use Modules\Payroll\Enums\AdjustmentStatus;
use Modules\Payroll\Enums\AdjustmentType;
use Modules\Payroll\Enums\CalculationType;
use Modules\Payroll\Enums\PayrollCycleType;
use Modules\Payroll\Enums\PayrollPaymentMethod;
use Modules\Payroll\Enums\PayRunStatus;
use Modules\Payroll\Enums\PayslipItemType;
use Modules\Payroll\Enums\PayslipStatus;
use Modules\Payroll\Enums\SalaryComponentType;
use Modules\Payroll\Models\PayrollAdjustment;
use Modules\Payroll\Models\PayrollEmployeeSalaryAssignment;
use Modules\Payroll\Models\PayrollPayPeriod;
use Modules\Payroll\Models\PayrollPayRun;
use Modules\Payroll\Models\PayrollPayslip;
use Modules\Payroll\Models\PayrollPayslipItem;
use Modules\Payroll\Models\PayrollSalaryComponent;
use Modules\Payroll\Models\PayrollSalaryStructure;
use Modules\Payroll\Models\PayrollSalaryStructureItem;
use Modules\Payroll\Models\PayrollStatutoryRule;
use Modules\Payroll\Models\PayrollTaxBracket;
use Modules\Payroll\Models\PayrollTaxTable;
use Modules\Payroll\Services\PayrollProrationService;
use Modules\Payroll\Services\PayrollTaxService;
use Modules\Payroll\Services\PayRunExecutionService;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class PayrollEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_proration_engine_for_standard_mid_joiner_and_leaver(): void
    {
        [$tenantId, $orgUnitId] = $this->scope();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $orgUnitId): void {
            $period = PayrollPayPeriod::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'name' => 'June 2026 Monthly',
                'cycle_type' => PayrollCycleType::Monthly,
                'period_start' => '2026-06-01',
                'period_end' => '2026-06-30',
                'payout_date' => '2026-06-30',
                'total_working_days' => 22,
            ]);

            $dept = app(HrDepartmentService::class)->create(new HrDepartmentData($tenantId, 'DEP-'.Str::random(4), 'Engineering', $orgUnitId));

            // 1. Standard full-month employee
            $empFull = $this->createEmployee($tenantId, $orgUnitId, 'EMP-FULL', $dept->id, '2025-01-01', null);
            $prorationFull = app(PayrollProrationService::class)->calculateProration($empFull, $period);
            $this->assertTrue($prorationFull['is_eligible']);
            $this->assertSame('22.00', $prorationFull['eligible_days']);
            $this->assertSame('1.0000', $prorationFull['proration_factor']);

            // 2. Mid-period joiner (joined June 15, 2026)
            $empJoiner = $this->createEmployee($tenantId, $orgUnitId, 'EMP-JOIN', $dept->id, '2026-06-15', null);
            $prorationJoiner = app(PayrollProrationService::class)->calculateProration($empJoiner, $period);
            $this->assertTrue($prorationJoiner['is_eligible']);
            $this->assertLessThan(22.0, (float) $prorationJoiner['eligible_days']);
            $this->assertLessThan(1.0, (float) $prorationJoiner['proration_factor']);
            $this->assertSame('Mid-period joiner', $prorationJoiner['proration_reason']);

            // 3. Mid-period leaver (resigned June 10, 2026)
            $empLeaver = $this->createEmployee($tenantId, $orgUnitId, 'EMP-LEAVE', $dept->id, '2025-01-01', '2026-06-10');
            $prorationLeaver = app(PayrollProrationService::class)->calculateProration($empLeaver, $period);
            $this->assertTrue($prorationLeaver['is_eligible']);
            $this->assertLessThan(22.0, (float) $prorationLeaver['eligible_days']);
            $this->assertSame('Mid-period leaver', $prorationLeaver['proration_reason']);

            // 4. Employee joined in July (not eligible for June)
            $empFuture = $this->createEmployee($tenantId, $orgUnitId, 'EMP-FUT', $dept->id, '2026-07-01', null);
            $prorationFuture = app(PayrollProrationService::class)->calculateProration($empFuture, $period);
            $this->assertFalse($prorationFuture['is_eligible']);
        });
    }

    public function test_progressive_tax_engine_calculation(): void
    {
        [$tenantId, $orgUnitId] = $this->scope();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $orgUnitId): void {
            $taxTable = PayrollTaxTable::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'code' => 'APIT_2026',
                'name' => 'Progressive Income Tax 2026',
                'effective_year' => 2026,
                'standard_personal_relief' => '100000.0000', // 100k tax-free personal allowance
                'is_active' => true,
            ]);

            // Bracket 1: 0 - 50,000 @ 6%
            PayrollTaxBracket::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'tax_table_id' => $taxTable->id,
                'bracket_order' => 1,
                'threshold_from' => '0.0000',
                'threshold_to' => '50000.0000',
                'rate_percentage' => '6.00',
            ]);

            // Bracket 2: 50,000 - 100,000 @ 12%
            PayrollTaxBracket::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'tax_table_id' => $taxTable->id,
                'bracket_order' => 2,
                'threshold_from' => '50000.0000',
                'threshold_to' => '100000.0000',
                'rate_percentage' => '12.00',
            ]);

            // Bracket 3: Above 100,000 @ 18%
            PayrollTaxBracket::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'tax_table_id' => $taxTable->id,
                'bracket_order' => 3,
                'threshold_from' => '100000.0000',
                'threshold_to' => null,
                'rate_percentage' => '18.00',
            ]);

            $taxService = app(PayrollTaxService::class);

            // Test 1: Under personal relief (90k income) -> 0 tax
            $this->assertSame('0.0000', $taxService->calculateTax($tenantId, '90000.0000', 2026));

            // Test 2: 130k income -> net taxable = 30k. Falls entirely in 6% bracket -> 30k * 6% = 1800
            $this->assertSame('1800.0000', $taxService->calculateTax($tenantId, '130000.0000', 2026));

            // Test 3: 180k income -> net taxable = 80k. Bracket 1: 50k * 6% = 3000. Bracket 2: 30k * 12% = 3600. Total = 6600
            $this->assertSame('6600.0000', $taxService->calculateTax($tenantId, '180000.0000', 2026));

            // Test 4: 250k income -> net taxable = 150k. Bracket 1: 3000. Bracket 2: 6000. Bracket 3: 50k * 18% = 9000. Total = 18000
            $this->assertSame('18000.0000', $taxService->calculateTax($tenantId, '250000.0000', 2026));
        });
    }

    public function test_end_to_end_batch_pay_run_calculation_with_attendance_and_leaves(): void
    {
        [$tenantId, $orgUnitId] = $this->scope();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $orgUnitId): void {
            $userId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'payroll-user-'.Str::lower(Str::random(8)).'@example.test',
            ]);

            // 1. Setup Period
            $period = PayrollPayPeriod::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'name' => 'June 2026 Monthly',
                'cycle_type' => PayrollCycleType::Monthly,
                'period_start' => '2026-06-01',
                'period_end' => '2026-06-30',
                'payout_date' => '2026-06-30',
                'total_working_days' => 20,
            ]);

            // 2. Setup Statutory Rules (EPF: 8% employee, 12% employer)
            PayrollStatutoryRule::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'code' => 'EPF',
                'name' => 'Employees Provident Fund',
                'employee_rate_percentage' => '8.00',
                'employer_rate_percentage' => '12.00',
                'is_active' => true,
            ]);

            // 3. Setup Salary Structure
            $basicComp = PayrollSalaryComponent::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'code' => 'BASIC',
                'name' => 'Basic Salary',
                'type' => SalaryComponentType::Earning,
                'calculation_type' => CalculationType::Fixed,
                'is_taxable' => true,
                'is_statutory_base' => true,
                'is_active' => true,
            ]);

            $transportComp = PayrollSalaryComponent::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'code' => 'TRANSPORT',
                'name' => 'Transport Allowance',
                'type' => SalaryComponentType::Earning,
                'calculation_type' => CalculationType::Fixed,
                'is_taxable' => true,
                'is_statutory_base' => false,
                'is_active' => true,
            ]);

            $structure = PayrollSalaryStructure::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'code' => 'STD_STAFF',
                'name' => 'Standard Staff Structure',
                'cycle_type' => PayrollCycleType::Monthly,
                'is_active' => true,
            ]);

            PayrollSalaryStructureItem::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'salary_structure_id' => $structure->id,
                'salary_component_id' => $transportComp->id,
                'calculation_type' => CalculationType::Fixed,
                'amount_or_rate' => '10000.0000',
            ]);

            // 4. Create Employee & Assign Salary ($200,000 base, $1,250/hr)
            $dept = app(HrDepartmentService::class)->create(new HrDepartmentData($tenantId, 'DEP-'.Str::random(4), 'Operations', $orgUnitId));
            $employee = $this->createEmployee($tenantId, $orgUnitId, 'EMP-PAY-1', $dept->id, '2025-01-01', null);

            PayrollEmployeeSalaryAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'employee_id' => $employee->id,
                'salary_structure_id' => $structure->id,
                'base_salary' => '200000.0000',
                'hourly_rate' => '1250.0000',
                'payment_method' => PayrollPaymentMethod::BankTransfer,
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ]);

            // 5. Add Attendance Overtime & Absence Records in June
            // 1 day absent (2026-06-05) -> Daily rate = 200,000 / 20 = 10,000 deduction
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-06-05',
                'status' => AttendanceStatus::Absent,
                'lock_status' => AttendanceLockStatus::Finalized,
            ]);

            // 4 hours normal OT (240 mins) on 2026-06-08 -> 4 * (1250 * 1.5 = 1875) = 7,500
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-06-08',
                'status' => AttendanceStatus::Present,
                'scheduled_minutes' => 480,
                'total_worked_minutes' => 720,
                'overtime_minutes' => 240,
                'double_time_minutes' => 0,
                'lock_status' => AttendanceLockStatus::Finalized,
            ]);

            // 6. Add 1 Unpaid Leave Day via HrLeaveRequest
            $unpaidLeaveType = HrLeaveType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'code' => 'UNPAID',
                'name' => 'Leave Without Pay',
                'is_paid' => false,
            ]);

            HrLeaveRequest::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'employee_id' => $employee->id,
                'leave_type_id' => $unpaidLeaveType->id,
                'start_date' => '2026-06-12',
                'end_date' => '2026-06-12',
                'duration_type' => LeaveDurationType::FullDay,
                'total_days' => '1.00',
                'reason' => 'Personal emergency',
                'status' => LeaveRequestStatus::Approved,
            ]);

            // 7. Add a Pending Bonus Adjustment of $5,000
            PayrollAdjustment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'employee_id' => $employee->id,
                'adjustment_type' => AdjustmentType::Addition,
                'amount' => '5000.0000',
                'effective_pay_period_id' => $period->id,
                'status' => AdjustmentStatus::Pending,
                'reason' => 'Performance achievement award',
            ]);

            // 8. Execute Pay Run via PayRunExecutionService
            $executionService = app(PayRunExecutionService::class);
            $payRun = $executionService->createPayRun($tenantId, (int) $period->id, (int) $structure->id, 'standard', 'June run', $userId);
            $this->assertSame(PayRunStatus::Draft, $payRun->status);

            // 9. Batch Calculate
            $calculatedRun = $executionService->calculatePayRun((int) $payRun->id, $userId);
            $this->assertSame(PayRunStatus::Calculated, $calculatedRun->status);
            $this->assertSame(1, $calculatedRun->total_employees);

            $payslip = $calculatedRun->payslips->first();
            $this->assertNotNull($payslip);

            // Calculations verification:
            // Prorated Basic = 200,000
            // Allowances = 10,000 (Transport)
            // Overtime = 7,500 (4 hrs @ 1,875)
            // Adjustment Bonus = 5,000
            // Gross Pay = 200,000 + 10,000 + 7,500 + 5,000 = 222,500.0000
            $this->assertSame('222500.0000', (string) $payslip->gross_pay);

            // Deductions:
            // Unpaid Leave (1 day @ 10,000) = 10,000
            // Absent (1 day @ 10,000) = 10,000
            // Total Deductions = 20,000.0000
            $this->assertSame('20000.0000', (string) $payslip->total_deductions);

            // Statutory: Basic = 200,000 -> 8% EPF Employee = 16,000.0000. Employer 12% = 24,000.0000
            $this->assertSame('16000.0000', (string) $payslip->employee_statutory_deductions);
            $this->assertSame('24000.0000', (string) $payslip->employer_statutory_contributions);

            // Net Pay = Gross (222,500) - Deductions (20,000) - EE Statutory (16,000) - Tax (0) = 186,500.0000
            $this->assertSame('186500.0000', (string) $payslip->net_pay);

            // 10. Approve Pay Run
            $approvedRun = $executionService->approvePayRun((int) $payRun->id, $userId);
            $this->assertSame(PayRunStatus::Approved, $approvedRun->status);
            $this->assertSame(PayslipStatus::Approved, $payslip->refresh()->status);

            // 11. Test Axiom 6 Immutability Guardrails on Approved Record
            $this->expectException(LogicException::class);
            $payslip->update(['gross_pay' => '300000.0000']);
        });
    }

    public function test_pay_run_payment_gl_journal_posting_and_reversal(): void
    {
        [$tenantId, $orgUnitId] = $this->scope();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $orgUnitId): void {
            $userId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'payroll-user-'.Str::lower(Str::random(8)).'@example.test',
            ]);

            $journal = FinanceJournalEntry::forceCreate([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'journal_number' => 'JRN-PAYROLL-555',
                'journal_type' => JournalType::General,
                'status' => JournalStatus::Posted,
                'journal_date' => now()->toDateString(),
                'source_module' => 'payroll',
                'source_type' => 'payroll_pay_run',
                'source_id' => 1,
                'source_key' => 'payroll_pay_run:1',
                'total_debit' => '150000.000000',
                'total_credit' => '150000.000000',
            ]);

            // Fake FinancePostingInterface to verify double-entry posting contract
            $fakeFinance = new class($journal->id) implements FinancePostingInterface
            {
                public ?PostingContext $lastContext = null;

                public bool $reversed = false;

                public function __construct(private readonly int $journalId) {}

                public function createDraftJournal(PostingContext $request): PostingResultData
                {
                    return new PostingResultData($this->journalId, 'JRN-DRAFT', 'draft', '0.000000', '0.000000');
                }

                public function validatePosting(PostingContext $request): void {}

                public function post(PostingContext $request, ?int $postedBy = null): PostingResultData
                {
                    $this->lastContext = $request;

                    return new PostingResultData($this->journalId, 'JRN-PAYROLL-555', 'posted', '150000.000000', '150000.000000');
                }

                public function postJournal(int $journalId, ?int $postedBy = null): PostingResultData
                {
                    return new PostingResultData($journalId, 'JRN-POSTED', 'posted', '150000.000000', '150000.000000');
                }

                public function reverseJournal(int $journalId, string $reversalDate, ?int $reversedBy = null, ?string $reason = null): PostingResultData
                {
                    $this->reversed = true;

                    return new PostingResultData($this->journalId, 'JRN-REV-556', 'reversed', '150000.000000', '150000.000000');
                }
            };

            $this->app->instance(FinancePostingInterface::class, $fakeFinance);

            $period = PayrollPayPeriod::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'name' => 'June 2026',
                'cycle_type' => PayrollCycleType::Monthly,
                'period_start' => '2026-06-01',
                'period_end' => '2026-06-30',
                'payout_date' => '2026-06-30',
                'total_working_days' => 20,
            ]);

            $structure = PayrollSalaryStructure::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'code' => 'TECH_1',
                'name' => 'Tech',
                'cycle_type' => PayrollCycleType::Monthly,
                'is_active' => true,
            ]);

            $dept = app(HrDepartmentService::class)->create(new HrDepartmentData($tenantId, 'DEP-'.Str::random(4), 'R&D', $orgUnitId));
            $employee = $this->createEmployee($tenantId, $orgUnitId, 'EMP-GL-1', $dept->id, '2025-01-01', null);

            PayrollEmployeeSalaryAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'employee_id' => $employee->id,
                'salary_structure_id' => $structure->id,
                'base_salary' => '150000.0000',
                'hourly_rate' => '937.5000',
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ]);

            $executionService = app(PayRunExecutionService::class);
            $payRun = $executionService->createPayRun($tenantId, (int) $period->id, (int) $structure->id, 'standard', 'GL test', $userId);
            $executionService->calculatePayRun((int) $payRun->id, $userId);
            $executionService->approvePayRun((int) $payRun->id, $userId);

            // Payout & GL Post
            $paidRun = $executionService->payPayRun((int) $payRun->id, $userId);
            $this->assertSame(PayRunStatus::Paid, $paidRun->status);
            $this->assertSame($journal->id, $paidRun->finance_journal_id);

            // Verify Finance Posting Lines Balance
            $this->assertNotNull($fakeFinance->lastContext);
            $totalDebit = '0.000000';
            $totalCredit = '0.000000';
            foreach ($fakeFinance->lastContext->lines as $line) {
                $totalDebit = bcadd($totalDebit, $line->debit, 6);
                $totalCredit = bcadd($totalCredit, $line->credit, 6);
            }
            $this->assertSame($totalDebit, $totalCredit, 'Debits and Credits must balance perfectly.');

            // Reversal
            $reversedRun = $executionService->reversePayRun((int) $payRun->id, $userId, 'Overpaid calculation correction');
            $this->assertSame(PayRunStatus::Reversed, $reversedRun->status);
            $this->assertTrue($fakeFinance->reversed);
            $this->assertSame(PayslipStatus::Reversed, $paidRun->payslips()->first()->status);
        });
    }

    public function test_lifecycle_guardrails_prevent_mutation_of_approved_payslip_items_and_applied_adjustments(): void
    {
        [$tenantId, $orgUnitId] = $this->scope();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $orgUnitId): void {
            $period = PayrollPayPeriod::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'name' => 'June 2026',
                'cycle_type' => PayrollCycleType::Monthly,
                'period_start' => '2026-06-01',
                'period_end' => '2026-06-30',
                'payout_date' => '2026-06-30',
                'total_working_days' => 20,
            ]);

            $structure = PayrollSalaryStructure::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'code' => 'TECH_G',
                'name' => 'Tech Guard',
                'cycle_type' => PayrollCycleType::Monthly,
                'is_active' => true,
            ]);

            $dept = app(HrDepartmentService::class)->create(new HrDepartmentData($tenantId, 'DEP-'.Str::random(4), 'R&D', $orgUnitId));
            $employee = $this->createEmployee($tenantId, $orgUnitId, 'EMP-GUARD-1', $dept->id, '2025-01-01', null);

            $payRun = PayrollPayRun::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'pay_run_number' => 'PR-GUARD-1',
                'pay_period_id' => $period->id,
                'salary_structure_id' => $structure->id,
                'status' => PayRunStatus::Approved,
            ]);

            $payslip = PayrollPayslip::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'payslip_number' => 'PS-GUARD-1',
                'pay_run_id' => $payRun->id,
                'employee_id' => $employee->id,
                'salary_structure_id' => $structure->id,
                'period_start' => '2026-06-01',
                'period_end' => '2026-06-30',
                'status' => PayslipStatus::Approved,
            ]);

            $item = PayrollPayslipItem::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'payslip_id' => $payslip->id,
                'item_type' => PayslipItemType::Earning,
                'item_code' => 'BASIC',
                'item_name' => 'Basic Salary',
                'rate' => '1000.0000',
                'hours_or_quantity' => '20.00',
                'amount' => '20000.0000',
            ]);

            // Attempting to modify or delete line item on approved payslip throws LogicException
            try {
                $item->update(['amount' => '25000.0000']);
                $this->fail('Expected LogicException on modifying item of approved payslip.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Line items cannot be modified', $e->getMessage());
            }

            try {
                $item->delete();
                $this->fail('Expected LogicException on deleting item of approved payslip.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Line items cannot be deleted', $e->getMessage());
            }

            // Applied adjustment guardrail
            $adjustment = PayrollAdjustment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'employee_id' => $employee->id,
                'adjustment_type' => AdjustmentType::Addition,
                'amount' => '2000.0000',
                'status' => AdjustmentStatus::Applied,
                'applied_payslip_id' => $payslip->id,
                'reason' => 'Bonus',
            ]);

            try {
                $adjustment->update(['amount' => '3000.0000']);
                $this->fail('Expected LogicException on modifying applied adjustment.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Applied adjustments cannot be modified directly', $e->getMessage());
            }

            try {
                $adjustment->delete();
                $this->fail('Expected LogicException on deleting applied adjustment.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Applied adjustments cannot be deleted', $e->getMessage());
            }
        });
    }

    private function createEmployee(int $tenantId, int $orgUnitId, string $number, int $departmentId, ?string $joinedDate, ?string $resignedDate): HrEmployee
    {
        return app(EmployeeCreationService::class)->create(new CreateEmployeeData(
            tenantId: $tenantId,
            organizationUnitId: $orgUnitId,
            employeeNumber: $number,
            firstName: $number,
            displayName: $number,
            departmentId: $departmentId,
            joinedDate: $joinedDate,
            resignedDate: $resignedDate,
            status: EmployeeStatus::Active,
        ));
    }

    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $currencyId = CurrencyFixture::create([
            'name' => "Payroll Currency {$suffix}",
            'symbol' => 'P',
        ]);
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => "TEN-PAY-{$suffix}",
            'name' => "Payroll Tenant {$suffix}",
            'slug' => 'pay-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => "Payroll Org {$suffix}",
            'code' => "ORG-{$suffix}",
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationId, $currencyId];
    }
}
