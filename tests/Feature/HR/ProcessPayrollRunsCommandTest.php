<?php

namespace Tests\Feature\HR;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\HR\Infrastructure\Jobs\ProcessPayslipJob;
use Tests\TestCase;

/**
 * Feature tests for the hr:process-payroll-runs Artisan command.
 *
 * Covers:
 *  - Draft payroll runs dispatch one payslip job per active employee.
 *  - Non-draft payroll runs (processing/completed) are skipped.
 *  - Inactive employees are not dispatched a payslip job.
 *  - --tenant option scopes processing to the specified tenant.
 *  - --chunk option is accepted and the command exits successfully.
 *  - An empty dataset (no draft runs) dispatches no jobs.
 */
class ProcessPayrollRunsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = (string) Str::uuid();

        // Bind a current tenant so HasTenantScope and creating hooks work.
        $this->app->instance('current.tenant.id', $this->tenantId);
    }

    // -----------------------------------------------------------------------
    // Draft run with active employees → jobs dispatched
    // -----------------------------------------------------------------------

    public function test_draft_run_dispatches_one_job_per_active_employee(): void
    {
        Queue::fake();

        $runId = $this->seedPayrollRun(status: 'draft');
        $this->seedEmployee(status: 'active');
        $this->seedEmployee(status: 'active');

        $this->artisan('hr:process-payroll-runs')
            ->assertSuccessful();

        Queue::assertPushed(ProcessPayslipJob::class, 2);
    }

    // -----------------------------------------------------------------------
    // No draft runs → no jobs dispatched
    // -----------------------------------------------------------------------

    public function test_no_draft_runs_dispatches_no_jobs(): void
    {
        Queue::fake();

        $this->artisan('hr:process-payroll-runs')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------------
    // Non-draft run (processing) is skipped
    // -----------------------------------------------------------------------

    public function test_processing_run_is_skipped(): void
    {
        Queue::fake();

        $this->seedPayrollRun(status: 'processing');
        $this->seedEmployee(status: 'active');

        $this->artisan('hr:process-payroll-runs')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------------
    // Inactive employees are not dispatched
    // -----------------------------------------------------------------------

    public function test_inactive_employees_are_skipped(): void
    {
        Queue::fake();

        $this->seedPayrollRun(status: 'draft');
        $this->seedEmployee(status: 'inactive');

        $this->artisan('hr:process-payroll-runs')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------------
    // Mixed active/inactive employees — only active receive jobs
    // -----------------------------------------------------------------------

    public function test_only_active_employees_receive_payslip_jobs(): void
    {
        Queue::fake();

        $this->seedPayrollRun(status: 'draft');
        $this->seedEmployee(status: 'active');
        $this->seedEmployee(status: 'inactive');
        $this->seedEmployee(status: 'active');

        $this->artisan('hr:process-payroll-runs')
            ->assertSuccessful();

        Queue::assertPushed(ProcessPayslipJob::class, 2);
    }

    // -----------------------------------------------------------------------
    // --tenant option scopes to the given tenant
    // -----------------------------------------------------------------------

    public function test_tenant_option_scopes_to_specified_tenant(): void
    {
        Queue::fake();

        // Draft run + active employee for the current tenant.
        $this->seedPayrollRun(status: 'draft');
        $this->seedEmployee(status: 'active');

        // Draft run + active employee for a different tenant.
        $otherTenantId = (string) Str::uuid();
        $this->seedPayrollRunForTenant($otherTenantId, status: 'draft');
        $this->seedEmployeeForTenant($otherTenantId, status: 'active');

        // Run only for the current tenant.
        $this->artisan('hr:process-payroll-runs', ['--tenant' => $this->tenantId])
            ->assertSuccessful();

        Queue::assertPushed(ProcessPayslipJob::class, 1);
    }

    // -----------------------------------------------------------------------
    // --chunk option is respected (command exits successfully)
    // -----------------------------------------------------------------------

    public function test_chunk_option_is_accepted_and_command_succeeds(): void
    {
        Queue::fake();

        $this->seedPayrollRun(status: 'draft');
        $this->seedEmployee(status: 'active');
        $this->seedEmployee(status: 'active');
        $this->seedEmployee(status: 'active');

        $this->artisan('hr:process-payroll-runs', ['--chunk' => 2])
            ->assertSuccessful();

        // All 3 active employees must receive jobs regardless of chunk size.
        Queue::assertPushed(ProcessPayslipJob::class, 3);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function seedPayrollRun(string $status): string
    {
        return $this->seedPayrollRunForTenant($this->tenantId, $status);
    }

    private function seedPayrollRunForTenant(string $tenantId, string $status): string
    {
        $id = (string) Str::uuid();

        \Illuminate\Support\Facades\DB::table('hr_payroll_runs')->insert([
            'id'           => $id,
            'tenant_id'    => $tenantId,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end'   => now()->endOfMonth()->toDateString(),
            'status'       => $status,
            'total_gross'  => '0.00000000',
            'total_net'    => '0.00000000',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $id;
    }

    private function seedEmployee(string $status): string
    {
        return $this->seedEmployeeForTenant($this->tenantId, $status);
    }

    private function seedEmployeeForTenant(string $tenantId, string $status): string
    {
        $id = (string) Str::uuid();

        \Illuminate\Support\Facades\DB::table('hr_employees')->insert([
            'id'         => $id,
            'tenant_id'  => $tenantId,
            'first_name' => 'Test',
            'last_name'  => 'Employee',
            'email'      => Str::uuid() . '@example.com',
            'position'   => 'Developer',
            'salary'     => '50000.00000000',
            'hire_date'  => now()->toDateString(),
            'status'     => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
