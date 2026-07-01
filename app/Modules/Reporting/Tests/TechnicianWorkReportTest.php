<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Models\UserModel;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;
use Modules\VehicleService\Services\VehicleServiceEmployeeAssignmentService;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceLineService;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

final class TechnicianWorkReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_report_returns_readable_rows_and_summary_totals(): void
    {
        $context = $this->context('READ');
        $this->authorize($context);
        $job = $this->createJob($context, date: '2026-06-01', supervisorCommissionType: VehicleServiceCommissionType::Fixed, supervisorCommissionValue: '15.000000');
        $line = $this->line($job, $context['labour'], '2.000000', '100.000000', 'Engine tune labour');

        $assignment = $this->assignment($job, $line, $context['employee_id'], 'technician', '2.000000', '50.000000', VehicleServiceCommissionType::Fixed, '30.000000');
        $this->assignment($job, $line, $context['employee_id'], 'helper', '1.000000', '25.000000', VehicleServiceCommissionType::Fixed, '10.000000');
        $this->linkInvoiceAndPayment($context, $job, InvoiceStatus::Posted);

        $this->reportGetJson($context, '/api/v1/reports/vehicle-service/technician-work?'.http_build_query($this->scope($context)))
            ->assertOk()
            ->assertJsonPath('data.0.id', (int) $assignment->getKey())
            ->assertJsonPath('data.0.job_number', (string) $job->job_number)
            ->assertJsonPath('data.0.job_date', '2026-06-01')
            ->assertJsonPath('data.0.customer.name', 'Customer CUS-READ')
            ->assertJsonPath('data.0.vehicle.name', 'REG-VEH-READ VEH-READ')
            ->assertJsonPath('data.0.employee.name', 'Technician EMP-READ')
            ->assertJsonPath('data.0.supervisor.name', 'Technician SUP-READ')
            ->assertJsonPath('data.0.line_description', 'Engine tune labour')
            ->assertJsonPath('data.0.line_source_type', 'labour_item')
            ->assertJsonPath('data.0.role_type', 'technician')
            ->assertJsonPath('data.0.assigned_hours', '2.000000')
            ->assertJsonPath('data.0.rate', '50.000000')
            ->assertJsonPath('data.0.labour_amount', '100.000000')
            ->assertJsonPath('data.0.line_total', '200.000000')
            ->assertJsonPath('data.0.commission_type', 'fixed')
            ->assertJsonPath('data.0.commission_value', '30.000000')
            ->assertJsonPath('data.0.commission_amount', '30.000000')
            ->assertJsonPath('data.0.supervisor_commission_amount', '15.000000')
            ->assertJsonPath('data.0.invoice_status', 'posted')
            ->assertJsonPath('data.0.payment_document_status', 'approved')
            ->assertJsonPath('data.0.payment_posting_status', 'posted')
            ->assertJsonPath('data.0.payment_allocation_status', 'fully_allocated')
            ->assertJsonPath('summary.total_assigned_hours', '3.000000')
            ->assertJsonPath('summary.total_labour_amount', '125.000000')
            ->assertJsonPath('summary.total_technician_commission', '40.000000')
            ->assertJsonPath('summary.total_supervisor_commission', '15.000000')
            ->assertJsonPath('summary.total_payable_commission', '55.000000');
    }

    public function test_report_filters_supported_fields(): void
    {
        $context = $this->context('FILT');
        $this->authorize($context);
        $matchingJob = $this->createJob($context, date: '2026-06-03', supervisorCommissionType: VehicleServiceCommissionType::Fixed, supervisorCommissionValue: '5.000000');
        $matchingLine = $this->line($matchingJob, $context['labour'], '1.000000', '100.000000', 'Precision alignment');
        $matching = $this->assignment($matchingJob, $matchingLine, $context['employee_id'], 'technician', '1.000000', '40.000000', VehicleServiceCommissionType::Percentage, '10.000000');
        $matchingJob = $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($matchingJob): VehicleServiceJob {
            $matchingJob->forceFill(['status' => VehicleServiceJobStatus::Completed])->save();

            return $matchingJob->refresh();
        });
        $this->linkInvoiceAndPayment($context, $matchingJob, InvoiceStatus::Paid);

        $other = $this->alternateResources($context, 'ALT');
        $otherJob = $this->createJob($other, date: '2026-05-20', status: VehicleServiceJobStatus::Draft);
        $otherLine = $this->line($otherJob, $other['labour'], '1.000000', '80.000000', 'Oil service');
        $this->assignment($otherJob, $otherLine, $other['employee_id'], 'helper', '1.000000', '20.000000', VehicleServiceCommissionType::None, '0.000000');
        $this->linkInvoiceAndPayment(
            $other,
            $otherJob,
            InvoiceStatus::Draft,
            PaymentDocumentStatus::Draft,
            PaymentPostingStatus::NotPosted,
            PaymentAllocationState::Unallocated,
        );

        $filters = [
            ['employee_id' => $context['employee_id']],
            ['supervisor_id' => $context['supervisor_id']],
            ['customer_id' => $context['customer_id']],
            ['vehicle_id' => $context['vehicle_id']],
            ['job_status' => 'completed'],
            ['role_type' => 'technician'],
            ['commission_type' => 'percentage'],
            ['invoice_status' => 'paid'],
            ['payment_allocation_status' => 'fully_allocated'],
            ['date_from' => '2026-06-01', 'date_to' => '2026-06-30'],
            ['search' => 'Precision'],
        ];

        foreach ($filters as $filter) {
            $this->reportGetJson($context, '/api/v1/reports/vehicle-service/technician-work?'.http_build_query([
                ...$this->scope($context),
                ...$filter,
            ]))
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.id', (int) $matching->getKey());
        }
    }

    public function test_report_enforces_tenant_and_organization_isolation(): void
    {
        $context = $this->context('TEN-A');
        $otherTenant = $this->context('TEN-B');
        $otherOrg = $this->alternateResources($context, 'ORG');

        $this->assignmentForContext($context, 'Tenant A work');
        $this->assignmentForContext($otherTenant, 'Tenant B work');
        $this->assignmentForContext($otherOrg, 'Other org work');
        $this->authorize($context);

        $this->reportGetJson($context, '/api/v1/reports/vehicle-service/technician-work?'.http_build_query($this->scope($context)))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.line_description', 'Tenant A work');
    }

    public function test_employee_commission_report_returns_dimensions_summaries_rankings_and_groups(): void
    {
        $context = $this->context('COM');
        $this->authorize($context);
        $other = $this->alternateResources($context, 'COM-ALT');
        $job = $this->createJob(
            $context,
            date: '2026-06-04',
            supervisorCommissionType: VehicleServiceCommissionType::Fixed,
            supervisorCommissionValue: '25.000000',
        );
        $line = $this->line($job, $context['labour'], '2.000000', '100.000000', 'Commission labour');
        $top = $this->assignment($job, $line, $context['employee_id'], 'technician', '2.000000', '50.000000', VehicleServiceCommissionType::Fixed, '30.000000');
        $helper = $this->assignment($job, $line, $other['employee_id'], 'helper', '1.000000', '40.000000', VehicleServiceCommissionType::Fixed, '10.000000');
        $this->linkInvoiceAndPayment($context, $job, InvoiceStatus::Posted);

        $response = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/employee-commissions?'.http_build_query([
            ...$this->scope($context),
            'group_by' => 'employee',
        ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('summary.total_entries', 3)
            ->assertJsonPath('summary.total_employees', 3)
            ->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('summary.total_hours', '3.000000')
            ->assertJsonPath('summary.total_labour_value', '140.000000')
            ->assertJsonPath('summary.total_commission_base', '600.000000')
            ->assertJsonPath('summary.technician_commission', '40.000000')
            ->assertJsonPath('summary.supervisor_commission', '25.000000')
            ->assertJsonPath('summary.total_commission', '65.000000')
            ->assertJsonPath('summary.average_commission_per_job', '65.000000')
            ->assertJsonPath('summary.average_commission_per_hour', '21.666666')
            ->assertJsonPath('rankings.top_earning_employee.employee.code', 'EMP-COM')
            ->assertJsonPath('rankings.top_earning_employee.labour_value', '100.000000')
            ->assertJsonPath('rankings.top_commission_employee.employee.code', 'EMP-COM');

        $response->assertJsonFragment([
            'id' => 'supervisor-'.$job->getKey(),
            'commission_source' => 'supervisor',
            'employee_code' => 'SUP-COM',
            'commission_base' => '200.000000',
            'commission_amount' => '25.000000',
        ]);

        $this->reportGetJson($context, '/api/v1/reports/vehicle-service/employee-commissions?'.http_build_query([
            ...$this->scope($context),
            'commission_source' => 'technician',
            'sort' => 'labour_amount',
            'direction' => 'asc',
        ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', 'technician-'.$helper->getKey());

        $this->reportGetJson($context, '/api/v1/reports/vehicle-service/employee-commissions?'.http_build_query([
            ...$this->scope($context),
            'commission_source' => 'supervisor',
        ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.employee.code', 'SUP-COM')
            ->assertJsonPath('data.0.commission_amount', '25.000000');
    }

    public function test_employee_commission_report_filters_exports_and_isolates_scope(): void
    {
        $context = $this->context('COM-FILT');
        $this->authorize($context);
        $otherOrg = $this->alternateResources($context, 'ORG');
        $matching = $this->assignmentForContext($context, 'Matching commission');
        $this->assignmentForContext($otherOrg, 'Other organization commission');
        $this->linkInvoiceAndPayment(
            $context,
            $this->withTenantExecutionContext(
                (int) $context['tenant_id'],
                fn (): VehicleServiceJob => VehicleServiceJob::query()->findOrFail($matching->vehicle_service_job_id),
            ),
            InvoiceStatus::Posted,
        );

        foreach ([
            ['employee_id' => $context['employee_id']],
            ['department_id' => $context['department_id']],
            ['designation_id' => $context['designation_id']],
            ['supervisor_id' => $context['supervisor_id']],
            ['customer_id' => $context['customer_id']],
            ['vehicle_id' => $context['vehicle_id']],
            ['job_status' => 'draft'],
            ['invoice_status' => 'posted'],
            ['payment_allocation_status' => 'fully_allocated'],
            ['commission_type' => 'fixed'],
            ['date_from' => '2026-06-01', 'date_to' => '2026-06-30'],
            ['search' => 'Matching commission'],
        ] as $filter) {
            $this->reportGetJson($context, '/api/v1/reports/vehicle-service/employee-commissions?'.http_build_query([
                ...$this->scope($context),
                ...$filter,
            ]))
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.id', 'technician-'.$matching->getKey());
        }

        $this->reportGet($context, '/api/v1/reports/vehicle-service/employee-commissions/export/csv?'.http_build_query($this->scope($context)))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('Employee code')
            ->assertSee('EMP-COM-FILT');
    }

    public function test_employee_commission_report_excludes_cancelled_work_by_default(): void
    {
        $context = $this->context('COM-CANCEL');
        $this->authorize($context);
        $job = $this->createJob(
            $context,
            supervisorCommissionType: VehicleServiceCommissionType::Fixed,
            supervisorCommissionValue: '50.000000',
        );
        $line = $this->line($job, $context['labour'], '1.000000', '100.000000', 'Cancelled commission');
        $assignment = $this->assignment(
            $job,
            $line,
            $context['employee_id'],
            'technician',
            '1.000000',
            '100.000000',
            VehicleServiceCommissionType::Fixed,
            '20.000000',
        );
        $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($job): void {
            $job->forceFill(['status' => VehicleServiceJobStatus::Cancelled])->save();
        });

        $this->reportGetJson($context, '/api/v1/reports/vehicle-service/employee-commissions?'.http_build_query($this->scope($context)))
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('summary.total_commission', '0.000000');

        $this->reportGetJson($context, '/api/v1/reports/vehicle-service/employee-commissions?'.http_build_query([
            ...$this->scope($context),
            'include_cancelled' => true,
        ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('summary.total_commission', '0.000000')
            ->assertJsonPath('summary.cancelled_commission', '70.000000')
            ->assertJsonFragment([
                'id' => 'technician-'.$assignment->getKey(),
                'commission_status' => 'cancelled',
            ])
            ->assertJsonFragment([
                'id' => 'supervisor-'.$job->getKey(),
                'commission_status' => 'cancelled',
            ]);
    }

    public function test_existing_generic_and_specialized_pdf_export_endpoints_keep_working(): void
    {
        $context = $this->context('PDF');
        $this->authorize($context);
        $this->assignmentForContext($context, 'PDF export assignment');
        Pdf::fake();

        foreach ([
            '/api/v1/reports/invoice.register/export/pdf' => 'invoice.register.pdf',
            '/api/v1/reports/vehicle-service/technician-work/export/pdf' => 'vehicle-service-technician-work.pdf',
            '/api/v1/reports/vehicle-service/employee-commissions/export/pdf' => 'vehicle-service-employee-commissions.pdf',
        ] as $path => $filename) {
            $this->reportGet($context, $path.'?'.http_build_query($this->scope($context)))->assertOk();

            Pdf::assertRespondedWithPdf(
                fn (PdfBuilder $pdf): bool => $pdf->viewName === (
                    str_starts_with($path, '/api/v1/reports/invoice.')
                        ? 'reports.finance.report'
                        : 'reports.vehicle-service.report'
                )
                    && $pdf->downloadName === $filename
                    && $pdf->viewData['mode'] === 'pdf',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function context(string $suffix): array
    {
        $tenantId = $this->tenant($suffix);
        $organizationUnitId = $this->organizationUnit($tenantId, 'ORG-'.$suffix);
        $uomId = $this->uom($tenantId, $organizationUnitId, 'PCS-'.$suffix);
        $customerId = $this->customer($tenantId, $organizationUnitId, 'CUS-'.$suffix);
        $vehicleId = $this->vehicle($tenantId, $organizationUnitId, $customerId, 'VEH-'.$suffix);
        $departmentId = $this->hrMaster($tenantId, $organizationUnitId, 'hr_departments', 'DEP-'.$suffix, 'Department '.$suffix);
        $designationId = $this->hrMaster($tenantId, $organizationUnitId, 'hr_designations', 'DES-'.$suffix, 'Designation '.$suffix);
        $employeeId = $this->employee($tenantId, $organizationUnitId, 'EMP-'.$suffix, $departmentId, $designationId);
        $supervisorId = $this->employee($tenantId, $organizationUnitId, 'SUP-'.$suffix, $departmentId, $designationId);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'employee_id' => $employeeId,
            'supervisor_id' => $supervisorId,
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'uom_id' => $uomId,
            'labour' => $this->item($tenantId, $organizationUnitId, 'LAB-'.$suffix, $uomId),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function alternateResources(array $context, string $suffix): array
    {
        $organizationUnitId = $suffix === 'ORG'
            ? $this->organizationUnit($context['tenant_id'], 'ORG-'.$suffix)
            : $context['organization_unit_id'];
        $uomId = $this->uom($context['tenant_id'], $organizationUnitId, 'PCS-'.$suffix);
        $customerId = $this->customer($context['tenant_id'], $organizationUnitId, 'CUS-'.$suffix);
        $vehicleId = $this->vehicle($context['tenant_id'], $organizationUnitId, $customerId, 'VEH-'.$suffix);
        $departmentId = $this->hrMaster($context['tenant_id'], $organizationUnitId, 'hr_departments', 'DEP-'.$suffix, 'Department '.$suffix);
        $designationId = $this->hrMaster($context['tenant_id'], $organizationUnitId, 'hr_designations', 'DES-'.$suffix, 'Designation '.$suffix);
        $employeeId = $this->employee($context['tenant_id'], $organizationUnitId, 'EMP-'.$suffix, $departmentId, $designationId);
        $supervisorId = $this->employee($context['tenant_id'], $organizationUnitId, 'SUP-'.$suffix, $departmentId, $designationId);

        return [
            ...$context,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'employee_id' => $employeeId,
            'supervisor_id' => $supervisorId,
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'uom_id' => $uomId,
            'labour' => $this->item($context['tenant_id'], $organizationUnitId, 'LAB-'.$suffix, $uomId),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, int>
     */
    private function scope(array $context): array
    {
        return [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'per_page' => 25,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function reportGetJson(array $context, string $uri): TestResponse
    {
        return $this->withTenantRequestContext(
            (int) $context['tenant_id'],
            (int) $context['user_id'],
            fn (): TestResponse => $this->getJson($uri),
            (int) $context['organization_unit_id'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function reportGet(array $context, string $uri): TestResponse
    {
        return $this->withTenantRequestContext(
            (int) $context['tenant_id'],
            (int) $context['user_id'],
            fn (): TestResponse => $this->get($uri),
            (int) $context['organization_unit_id'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function assignmentForContext(array $context, string $description): VehicleServiceLineEmployee
    {
        $job = $this->createJob($context);
        $line = $this->line($job, $context['labour'], '1.000000', '10.000000', $description);

        return $this->assignment($job, $line, $context['employee_id'], 'technician', '1.000000', '10.000000', VehicleServiceCommissionType::Fixed, '1.000000');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createJob(
        array $context,
        string $date = '2026-06-07',
        VehicleServiceJobStatus $status = VehicleServiceJobStatus::Draft,
        VehicleServiceCommissionType $supervisorCommissionType = VehicleServiceCommissionType::None,
        string $supervisorCommissionValue = '0.000000',
    ): VehicleServiceJob {
        return $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($context, $date, $status, $supervisorCommissionType, $supervisorCommissionValue): VehicleServiceJob {
            $job = app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
                tenantId: $context['tenant_id'],
                jobDate: $date,
                customerId: $context['customer_id'],
                vehicleId: $context['vehicle_id'],
                organizationUnitId: $context['organization_unit_id'],
                supervisorEmployeeId: $context['supervisor_id'],
                supervisorCommissionType: $supervisorCommissionType,
                supervisorCommissionValue: $supervisorCommissionValue,
            ));

            if ($status !== VehicleServiceJobStatus::Draft) {
                $job->forceFill(['status' => $status])->save();
            }

            return $job->refresh();
        });
    }

    private function line(VehicleServiceJob $job, Item $item, string $quantity, string $unitPrice, string $description)
    {
        return $this->withTenantExecutionContext((int) $job->tenant_id, fn () => app(VehicleServiceLineService::class)->create($job, new VehicleServiceLineData(
            lineSourceType: VehicleServiceLineSourceType::LabourItem,
            description: $description,
            quantity: $quantity,
            unitPrice: $unitPrice,
            itemId: (int) $item->getKey(),
            uomId: (int) $item->base_uom_id,
        )));
    }

    private function assignment(
        VehicleServiceJob $job,
        mixed $line,
        int $employeeId,
        string $roleType,
        string $assignedHours,
        string $rate,
        VehicleServiceCommissionType $commissionType,
        string $commissionValue,
    ): VehicleServiceLineEmployee {
        return $this->withTenantExecutionContext((int) $job->tenant_id, fn (): VehicleServiceLineEmployee => app(VehicleServiceEmployeeAssignmentService::class)->create($job, $line, new VehicleServiceEmployeeAssignmentData(
            employeeId: $employeeId,
            roleType: $roleType,
            assignedHours: $assignedHours,
            rate: $rate,
            commissionType: $commissionType,
            commissionValue: $commissionValue,
        )));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function linkInvoiceAndPayment(
        array $context,
        VehicleServiceJob $job,
        InvoiceStatus $invoiceStatus,
        PaymentDocumentStatus $paymentDocumentStatus = PaymentDocumentStatus::Approved,
        PaymentPostingStatus $paymentPostingStatus = PaymentPostingStatus::Posted,
        PaymentAllocationState $paymentAllocationStatus = PaymentAllocationState::FullyAllocated,
    ): void
    {
        $now = now();
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'invoice_type' => InvoiceType::Service->value,
            'direction' => InvoiceDirection::Outbound->value,
            'party_type' => 'customer',
            'party_id' => $context['customer_id'],
            'invoice_date' => '2026-06-07',
            'status' => $invoiceStatus->value,
            'grand_total' => '100.000000',
            'balance_due' => '0.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('vehicle_service_invoice_links')->insert([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'vehicle_service_job_id' => $job->getKey(),
            'invoice_id' => $invoiceId,
            'source_line_total' => '100.000000',
            'allocated_adjustment_total' => '0.000000',
            'invoice_total' => '100.000000',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'payment_number' => 'PAY-'.Str::upper(Str::random(8)),
            'payment_type' => PaymentType::ServiceReceipt->value,
            'direction' => PaymentDirection::Inbound->value,
            'party_type' => 'customer',
            'party_id' => $context['customer_id'],
            'payment_date' => '2026-06-07',
            'document_status' => $paymentDocumentStatus->value,
            'posting_status' => $paymentPostingStatus->value,
            'allocation_status' => $paymentAllocationStatus->value,
            'total_amount' => '100.000000',
            'allocated_amount' => $paymentAllocationStatus === PaymentAllocationState::Unallocated ? '0.000000' : '100.000000',
            'unapplied_amount' => $paymentAllocationStatus === PaymentAllocationState::Unallocated ? '100.000000' : '0.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('vehicle_service_payment_links')->insert([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'vehicle_service_job_id' => $job->getKey(),
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'allocated_amount' => '100.000000',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function item(int $tenantId, int $organizationUnitId, string $code, int $uomId): Item
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Item => app(ItemCreationService::class)->create(new CreateItemData(
                tenantId: $tenantId,
                code: $code,
                name: str_replace('-', ' ', $code),
                itemType: ItemType::Labour,
                organizationUnitId: $organizationUnitId,
                trackingType: TrackingType::None,
                costingMethod: CostingMethod::None,
                baseUomId: $uomId,
                isStockable: false,
            )),
        );
    }

    private function tenant(string $suffix): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-REP-'.$suffix,
            'name' => 'Report Tenant '.$suffix,
            'slug' => 'report-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function organizationUnit(int $tenantId, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Org '.$code,
            'code' => $code,
            'path' => '/'.Str::lower($code),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uom(int $tenantId, int $organizationUnitId, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => 1,
            'code' => $code,
            'name' => 'Unit '.$code,
            'symbol' => 'hrs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customer(int $tenantId, int $organizationUnitId, string $code): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => $code,
            'code' => $code,
            'name' => 'Customer '.$code,
            'display_name' => 'Customer '.$code,
            'customer_type' => 'individual',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function vehicle(int $tenantId, int $organizationUnitId, int $customerId, string $code): int
    {
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_number' => $code,
            'registration_number' => 'REG-'.$code,
            'odometer_reading' => '12000.000000',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vehicle_ownerships')->insert([
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_id' => $vehicleId,
            'owner_type' => 'customer',
            'owner_id' => $customerId,
            'owner_key' => 'customer:'.$customerId,
            'owner_code_snapshot' => $code,
            'owner_name_snapshot' => 'Customer '.$code,
            'ownership_type' => 'customer_owned',
            'started_at' => now(),
            'is_current' => true,
            'current_guard' => 1,
            'active_guard' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $vehicleId;
    }

    private function employee(int $tenantId, int $organizationUnitId, string $code, int $departmentId, int $designationId): int
    {
        return (int) DB::table('hr_employees')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'employee_number' => $code,
            'code' => $code,
            'first_name' => 'Tech',
            'display_name' => 'Technician '.$code,
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'status' => 'active',
            'availability_status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function hrMaster(int $tenantId, int $organizationUnitId, string $table, string $code, string $name): int
    {
        return (int) DB::table($table)->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $context */
    private function authorize(array &$context): void
    {
        $tenantId = (int) $context['tenant_id'];
        $organizationUnitId = (int) $context['organization_unit_id'];
        $guard = UserGuard::TENANT_API;
        $now = now();
        $userId = (int) \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Reporting',
            'last_name' => 'Administrator',
            'email' => 'reporting-admin-'.Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('secret-password'),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => UserSystemRole::SUPER_ADMIN_NAME,
            'active_name_key' => mb_strtolower(UserSystemRole::SUPER_ADMIN_NAME),
            'guard_name' => $guard,
            'system_key' => UserSystemRole::SUPER_ADMIN,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionIds = [];
        foreach (ReportingAuthorizationService::descriptions() as $name => $description) {
            $permissionIds[] = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $name,
                'guard_name' => $guard,
                'module' => 'Reporting',
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        ));
        $context['user_id'] = $userId;
    }
}
