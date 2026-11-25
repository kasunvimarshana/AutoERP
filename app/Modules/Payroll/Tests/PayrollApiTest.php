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
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\DTOs\HrDepartmentData;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Services\EmployeeCreationService;
use Modules\Hr\Services\HrDepartmentService;
use Modules\Payroll\Enums\PayRunStatus;
use Modules\User\Constants\UserGuard;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class PayrollApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_api_crud_endpoints_and_pay_run_workflow(): void
    {
        [$tenantId, $orgUnitId] = $this->scope();
        $email = 'payroll-api-'.Str::lower(Str::random(8)).'@example.test';
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => $email,
            'password' => 'secret-password',
        ]);

        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgUnitId,
            'user_id' => $userId,
            'status' => 'active',
            'is_default' => true,
            'default_marker' => 'default',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantAuthenticationFixture::provision($tenantId, $userId, $email);

        $provisioned = $this->withTenantExecutionContext(
            $tenantId,
            static fn (): array => app(TenantAccessProvisionerInterface::class)->provision($tenantId),
        );
        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $provisioned['role_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = (string) $this->withHeader('X-Tenant-Id', (string) $tenantId)->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $orgUnitId,
            'identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => (string) $tenantId,
        ];

        // Mock Finance posting
        $journal = $this->withTenantExecutionContext($tenantId, fn () => FinanceJournalEntry::forceCreate([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgUnitId,
            'journal_number' => 'JRN-API-101',
            'journal_type' => JournalType::General,
            'status' => JournalStatus::Posted,
            'journal_date' => now()->toDateString(),
            'source_module' => 'payroll',
            'source_type' => 'payroll_pay_run',
            'source_id' => 1,
            'source_key' => 'payroll_pay_run:api:1',
            'total_debit' => '100000.000000',
            'total_credit' => '100000.000000',
        ]));

        $fakeFinance = new class($journal->id) implements FinancePostingInterface, FinanceSourceReversalInterface
        {
            public bool $reversed = false;

            public function __construct(private readonly int $journalId) {}

            public function createDraftJournal(PostingContext $request): PostingResultData
            {
                return new PostingResultData($this->journalId, 'JRN-DRAFT', 'draft', '0.000000', '0.000000');
            }

            public function validatePosting(PostingContext $request): void {}

            public function post(PostingContext $request, ?int $postedBy = null): PostingResultData
            {
                return new PostingResultData($this->journalId, 'JRN-API-101', 'posted', '100000.000000', '100000.000000');
            }

            public function postJournal(int $journalId, ?int $postedBy = null): PostingResultData
            {
                return new PostingResultData($journalId, 'JRN-POSTED', 'posted', '100000.000000', '100000.000000');
            }

            public function reverseJournal(int $journalId, string $reversalDate, ?int $reversedBy = null, ?string $reason = null): PostingResultData
            {
                $this->reversed = true;

                return new PostingResultData($this->journalId, 'JRN-REV-101', 'reversed', '100000.000000', '100000.000000');
            }

            public function reverseSource(
                int $tenantId,
                ?int $organizationUnitId,
                string $sourceModule,
                string $sourceType,
                int $sourceId,
                string $reversalDate,
                ?int $reversedBy = null,
                ?string $reason = null,
            ): PostingResultData {
                $this->reversed = true;

                return new PostingResultData($this->journalId, 'JRN-REV-101', 'reversed', '100000.000000', '100000.000000');
            }
        };

        $this->app->instance(FinancePostingInterface::class, $fakeFinance);
        $this->app->instance(FinanceSourceReversalInterface::class, $fakeFinance);

        // 1. Create Component via API
        $compResp = $this->withHeaders($headers)->postJson('/api/v1/payroll/components', [
            'tenant_id' => $tenantId,
            'code' => 'HOUSING',
            'name' => 'Housing Allowance',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'is_taxable' => true,
            'is_statutory_base' => false,
        ]);
        $compResp->assertStatus(201);
        $compId = $compResp->json('data.id');

        // 2. List Components
        $listComp = $this->withHeaders($headers)->getJson("/api/v1/payroll/components?tenant_id={$tenantId}");
        $listComp->assertStatus(200);
        $this->assertNotEmpty($listComp->json('data'));

        // 3. Create Structure via API
        $structResp = $this->withHeaders($headers)->postJson('/api/v1/payroll/structures', [
            'tenant_id' => $tenantId,
            'code' => 'EXECUTIVE',
            'name' => 'Executive Grade Structure',
            'cycle_type' => 'monthly',
            'items' => [
                [
                    'salary_component_id' => $compId,
                    'calculation_type' => 'fixed',
                    'amount_or_rate' => 25000,
                    'sort_order' => 1,
                ],
            ],
        ]);
        $structResp->assertStatus(201);
        $structId = $structResp->json('data.id');

        // 4. Create Employee & Assign Compensation
        $dept = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(HrDepartmentService::class)->create(new HrDepartmentData($tenantId, 'DEP-'.Str::random(4), 'Executive', $orgUnitId))
        );
        $employee = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(EmployeeCreationService::class)->create(new CreateEmployeeData(
                tenantId: $tenantId,
                organizationUnitId: $orgUnitId,
                employeeNumber: 'EMP-API-1',
                firstName: 'Ruwan',
                displayName: 'Ruwan Silva',
                departmentId: $dept->id,
                status: EmployeeStatus::Active,
            ))
        );

        $assignResp = $this->withHeaders($headers)->postJson('/api/v1/payroll/assignments', [
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'salary_structure_id' => $structId,
            'base_salary' => 150000,
            'hourly_rate' => 937.5,
            'effective_from' => '2026-01-01',
        ]);
        $assignResp->assertStatus(201);

        // 5. Create Pay Period
        $periodResp = $this->withHeaders($headers)->postJson('/api/v1/payroll/periods', [
            'tenant_id' => $tenantId,
            'name' => 'May 2026 Monthly',
            'cycle_type' => 'monthly',
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'payout_date' => '2026-05-31',
            'total_working_days' => 21,
        ]);
        $periodResp->assertStatus(201);
        $periodId = $periodResp->json('data.id');

        // 6. Create Pay Run
        $payRunResp = $this->withHeaders($headers)->postJson('/api/v1/payroll/pay-runs', [
            'tenant_id' => $tenantId,
            'pay_period_id' => $periodId,
            'salary_structure_id' => $structId,
        ]);
        $payRunResp->assertStatus(201);
        $payRunId = $payRunResp->json('data.id');

        // 7. Calculate Pay Run via API (with optimistic concurrency check)
        $payRunVersion = (int) $payRunResp->json('data.row_version');
        $this->withHeaders($headers)->postJson("/api/v1/payroll/pay-runs/{$payRunId}/calculate", [
            'tenant_id' => $tenantId,
            'expected_version' => $payRunVersion + 99,
        ])->assertStatus(409);

        $calcResp = $this->withHeaders($headers)->postJson("/api/v1/payroll/pay-runs/{$payRunId}/calculate", [
            'tenant_id' => $tenantId,
            'expected_version' => $payRunVersion,
        ]);
        $calcResp->assertStatus(200);
        $this->assertSame(PayRunStatus::Calculated->value, $calcResp->json('data.status'));
        $this->assertSame(1, $calcResp->json('data.total_employees'));
        $payRunVersion = (int) $calcResp->json('data.row_version');

        // 7b. Recalculate Pay Run (verifies draft payslip + item replacement under restrictOnDelete)
        $recalcResp = $this->withHeaders($headers)->postJson("/api/v1/payroll/pay-runs/{$payRunId}/calculate", [
            'tenant_id' => $tenantId,
            'expected_version' => $payRunVersion,
        ]);
        $recalcResp->assertStatus(200);
        $this->assertSame(PayRunStatus::Calculated->value, $recalcResp->json('data.status'));
        $this->assertSame(1, $recalcResp->json('data.total_employees'));
        $payRunVersion = (int) $recalcResp->json('data.row_version');

        // 8. Approve Pay Run
        $appResp = $this->withHeaders($headers)->postJson("/api/v1/payroll/pay-runs/{$payRunId}/approve", [
            'tenant_id' => $tenantId,
            'expected_version' => $payRunVersion,
        ]);
        $appResp->assertStatus(200);
        $this->assertSame(PayRunStatus::Approved->value, $appResp->json('data.status'));
        $payRunVersion = (int) $appResp->json('data.row_version');

        // 9. Pay and Post to GL
        $payResp = $this->withHeaders($headers)->postJson("/api/v1/payroll/pay-runs/{$payRunId}/pay", [
            'tenant_id' => $tenantId,
            'expected_version' => $payRunVersion,
        ]);
        $payResp->assertStatus(200);
        $this->assertSame(PayRunStatus::Paid->value, $payResp->json('data.status'));
        $payRunVersion = (int) $payResp->json('data.row_version');

        // 10. List Payslips
        $slipResp = $this->withHeaders($headers)->getJson("/api/v1/payroll/payslips?tenant_id={$tenantId}&pay_run_id={$payRunId}");
        $slipResp->assertStatus(200);
        $this->assertCount(1, $slipResp->json('data'));
        $slipId = $slipResp->json('data.0.id');

        // 11. View Individual Payslip
        $showSlip = $this->withHeaders($headers)->getJson("/api/v1/payroll/payslips/{$slipId}?tenant_id={$tenantId}");
        $showSlip->assertStatus(200);
        $this->assertNotEmpty($showSlip->json('data.items'));

        // 12. Reverse Pay Run
        $revResp = $this->withHeaders($headers)->postJson("/api/v1/payroll/pay-runs/{$payRunId}/reverse", [
            'tenant_id' => $tenantId,
            'expected_version' => $payRunVersion,
            'reason' => 'Audit review reversal',
        ]);
        $revResp->assertStatus(200);
        $this->assertSame(PayRunStatus::Reversed->value, $revResp->json('data.status'));
        $this->assertTrue($fakeFinance->reversed);

        // 13. Loan Application, Approval, and Disbursement API
        $loanResp = $this->withHeaders($headers)->postJson('/api/v1/payroll/loans', [
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'loan_type' => 'loan',
            'principal_amount' => 50000,
            'term_months' => 10,
            'annual_interest_rate' => 0,
            'notes' => 'API test loan',
        ]);
        $loanResp->assertStatus(201);
        $loanId = $loanResp->json('data.id');
        $this->assertSame('draft', $loanResp->json('data.status'));

        // List Loans
        $listLoansResp = $this->withHeaders($headers)->getJson("/api/v1/payroll/loans?tenant_id={$tenantId}");
        $listLoansResp->assertStatus(200);

        // Approve Loan
        $apprLoanResp = $this->withHeaders($headers)->postJson("/api/v1/payroll/loans/{$loanId}/approve", [
            'tenant_id' => $tenantId,
        ]);
        $apprLoanResp->assertStatus(200);
        $this->assertSame('approved', $apprLoanResp->json('data.status'));

        // Disburse Loan
        $disbLoanResp = $this->withHeaders($headers)->postJson("/api/v1/payroll/loans/{$loanId}/disburse", [
            'tenant_id' => $tenantId,
            'disbursed_date' => '2026-06-01',
        ]);
        $disbLoanResp->assertStatus(200);
        $this->assertSame('active', $disbLoanResp->json('data.status'));

        // 14. Retroactive Arrears API
        $retroResp = $this->withHeaders($headers)->postJson('/api/v1/payroll/adjustments/calculate-retro-arrears', [
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'effective_from_date' => '2026-05-01',
            'target_pay_period_id' => $periodId,
        ]);
        $retroResp->assertStatus(200);

        // 15. Unprivileged user is rejected with 403 by tenant.permission middleware
        $unprivEmail = 'payroll-unpriv-'.Str::lower(Str::random(8)).'@example.test';
        $unprivUserId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => $unprivEmail,
            'password' => 'secret-password',
        ]);
        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgUnitId,
            'user_id' => $unprivUserId,
            'status' => 'active',
            'is_default' => true,
            'default_marker' => 'default',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        TenantAuthenticationFixture::provision($tenantId, $unprivUserId, $unprivEmail);
        $unprivToken = (string) $this->withHeader('X-Tenant-Id', (string) $tenantId)->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $orgUnitId,
            'identifier' => $unprivEmail,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        $this->withHeaders([
            'Authorization' => "Bearer {$unprivToken}",
            'X-Tenant-Id' => (string) $tenantId,
        ])->getJson("/api/v1/payroll/components?tenant_id={$tenantId}")->assertStatus(403);

        // 16. Granular permission check: user with only payroll.view can read pay-runs but cannot create/calculate
        $viewEmail = 'payroll-view-'.Str::lower(Str::random(8)).'@example.test';
        $viewUserId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => $viewEmail,
            'password' => 'secret-password',
        ]);
        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgUnitId,
            'user_id' => $viewUserId,
            'status' => 'active',
            'is_default' => true,
            'default_marker' => 'default',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        TenantAuthenticationFixture::provision($tenantId, $viewUserId, $viewEmail);
        $guardName = UserGuard::TENANT_API;
        $roleName = 'Payroll View Only '.Str::upper(Str::random(4));
        $viewOnlyRoleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $roleName,
            'active_name_key' => Str::lower($roleName),
            'guard_name' => $guardName,
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $viewPermId = (int) DB::table('permissions')
            ->where('tenant_id', $tenantId)
            ->where('guard_name', $guardName)
            ->where('name', 'payroll.view')
            ->value('id');
        DB::table('role_permissions')->insert([
            'tenant_id' => $tenantId,
            'role_id' => $viewOnlyRoleId,
            'permission_id' => $viewPermId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $viewUserId,
            'role_id' => $viewOnlyRoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $viewToken = (string) $this->withHeader('X-Tenant-Id', (string) $tenantId)->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $orgUnitId,
            'identifier' => $viewEmail,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        $this->withHeaders([
            'Authorization' => "Bearer {$viewToken}",
            'X-Tenant-Id' => (string) $tenantId,
        ])->getJson("/api/v1/payroll/pay-runs?tenant_id={$tenantId}")->assertOk();

        $this->withHeaders([
            'Authorization' => "Bearer {$viewToken}",
            'X-Tenant-Id' => (string) $tenantId,
        ])->postJson("/api/v1/payroll/pay-runs?tenant_id={$tenantId}", [
            'pay_period_id' => $periodId,
            'salary_structure_id' => $structId,
        ])->assertStatus(403);

        // 16. Soft-delete an unused Salary Structure and verify child items remain intact for audit
        $tempStructResp = $this->withHeaders($headers)->postJson('/api/v1/payroll/structures', [
            'tenant_id' => $tenantId,
            'code' => 'TEMP-DEL',
            'name' => 'Temporary Structure',
            'cycle_type' => 'monthly',
            'items' => [
                [
                    'salary_component_id' => $compId,
                    'calculation_type' => 'fixed',
                    'amount_or_rate' => 5000,
                    'sort_order' => 1,
                ],
            ],
        ]);
        $tempStructResp->assertStatus(201);
        $tempStructId = (int) $tempStructResp->json('data.id');

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/payroll/structures/{$tempStructId}?tenant_id={$tenantId}")
            ->assertOk();

        $this->assertSoftDeleted('payroll_salary_structures', ['id' => $tempStructId]);
        $this->assertDatabaseHas('payroll_salary_structure_items', ['salary_structure_id' => $tempStructId]);
    }

    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $currencyId = CurrencyFixture::create([
            'name' => "API Currency {$suffix}",
            'symbol' => 'A',
        ]);
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => "TEN-API-{$suffix}",
            'name' => "API Tenant {$suffix}",
            'slug' => 'api-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => "API Org {$suffix}",
            'code' => "ORG-{$suffix}",
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ActiveTenantSubscriptionFixture::create($tenantId);

        return [$tenantId, $organizationId, $currencyId];
    }
}
