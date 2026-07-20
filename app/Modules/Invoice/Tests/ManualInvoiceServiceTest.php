<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\ManualInvoiceData;
use Modules\Invoice\DTOs\ManualInvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceReversalService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Invoice\Services\ManualInvoiceService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class ManualInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_invoice_creation_is_idempotent(): void
    {
        $tenantId = $this->createTenant();
        $organizationUnitId = $this->createOrganizationUnit($tenantId);
        $customerId = $this->createCustomer($tenantId, $organizationUnitId);
        $data = $this->invoiceData($tenantId, $organizationUnitId, $customerId, '100.000000');
        $service = app(ManualInvoiceService::class);

        $first = $this->withTenantExecutionContext($tenantId, fn () => $service->create($data, 'manual-invoice-request-1'));
        $second = $this->withTenantExecutionContext($tenantId, fn () => $service->create($data, 'manual-invoice-request-1'));

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(1, $this->withTenantExecutionContext($tenantId, fn () => Invoice::query()->where('tenant_id', $tenantId)->count()));
    }

    public function test_invoice_status_actions_reject_stale_versions(): void
    {
        $tenantId = $this->createTenant();
        $organizationUnitId = $this->createOrganizationUnit($tenantId);
        $customerId = $this->createCustomer($tenantId, $organizationUnitId);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(ManualInvoiceService::class)->create(
            $this->invoiceData($tenantId, $organizationUnitId, $customerId, '100.000000'),
            'manual-invoice-version-test',
        ));
        $initialVersion = (int) $invoice->row_version;

        $approved = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceStatusService::class)->transitionIfVersion(
            $invoice,
            InvoiceStatus::Approved,
            $initialVersion,
        ));

        $this->assertGreaterThan($initialVersion, (int) $approved->row_version);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice was changed by another request. Reload it before performing this action.');

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceStatusService::class)->transitionIfVersion(
            $approved,
            InvoiceStatus::Posted,
            $initialVersion,
        ));
    }

    public function test_non_draft_invoices_cannot_be_deleted(): void
    {
        $tenantId = $this->createTenant();
        $organizationUnitId = $this->createOrganizationUnit($tenantId);
        $customerId = $this->createCustomer($tenantId, $organizationUnitId);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(ManualInvoiceService::class)->create(
            $this->invoiceData($tenantId, $organizationUnitId, $customerId, '100.000000'),
            'manual-invoice-delete-test',
        ));
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceStatusService::class)->transition($invoice, InvoiceStatus::Approved));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only draft invoices can be deleted.');

        $this->withTenantExecutionContext($tenantId, fn () => $invoice->delete());
    }

    public function test_retired_vehicle_finance_invoice_rejects_non_settlement_lifecycle_changes(): void
    {
        $tenantId = $this->createTenant();
        $organizationUnitId = $this->createOrganizationUnit($tenantId);
        $customerId = $this->createCustomer($tenantId, $organizationUnitId);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(ManualInvoiceService::class)->create(
            $this->invoiceData($tenantId, $organizationUnitId, $customerId, '100.000000'),
            'retired-vehicle-finance-lifecycle',
        ));
        DB::table('invoices')->where('id', $invoice->getKey())->update([
            'invoice_type' => InvoiceType::VehicleFinance->value,
        ]);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => $invoice->refresh());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Historical Vehicle Finance invoices are read-only except for settlement updates.',
        );

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceStatusService::class)->transitionIfVersion(
            $invoice,
            InvoiceStatus::Cancelled,
            (int) $invoice->row_version,
        ));
    }

    public function test_retired_vehicle_finance_invoice_rejects_reversal_before_financial_mutation(): void
    {
        $tenantId = $this->createTenant();
        $organizationUnitId = $this->createOrganizationUnit($tenantId);
        $customerId = $this->createCustomer($tenantId, $organizationUnitId);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(ManualInvoiceService::class)->create(
            $this->invoiceData($tenantId, $organizationUnitId, $customerId, '100.000000'),
            'retired-vehicle-finance-reversal',
        ));
        DB::table('invoices')->where('id', $invoice->getKey())->update([
            'invoice_type' => InvoiceType::VehicleFinance->value,
            'status' => InvoiceStatus::Posted->value,
            'posted_at' => now(),
        ]);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => $invoice->refresh());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Historical Vehicle Finance invoices cannot be reversed after their source module was retired.',
        );

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceReversalService::class)->reverse(
            $invoice,
            (int) $invoice->row_version,
            now()->toDateString(),
            'Retired source safety check.',
        ));
    }

    public function test_requested_posted_status_uses_governed_issuance_transitions(): void
    {
        $tenantId = $this->createTenant();
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
        $postingPlan = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-29',
            FinanceAccountRoleCode::Revenue,
            '100.000000',
            description: 'Governed issuance',
        );

        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-29',
            status: InvoiceStatus::Posted,
            lines: [new InvoiceLineData(
                lineNumber: 1,
                description: 'Governed issuance',
                quantity: '1.000000',
                unitPrice: '100.000000',
            )],
            postingPlan: $postingPlan,
        )));

        $this->assertSame(InvoiceStatus::Posted, $invoice->status);
        $this->assertNotNull($invoice->approved_at);
        $this->assertNotNull($invoice->posted_at);
        $this->assertSame(3, (int) $invoice->row_version);
    }

    public function test_invoice_keeps_party_snapshot_after_master_data_changes(): void
    {
        $tenantId = $this->createTenant();
        $organizationUnitId = $this->createOrganizationUnit($tenantId);
        $customerId = $this->createCustomer($tenantId, $organizationUnitId);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(ManualInvoiceService::class)->create(
            $this->invoiceData($tenantId, $organizationUnitId, $customerId, '100.000000'),
            'manual-invoice-snapshot-test',
        ));
        $originalName = (string) $invoice->party_name_snapshot;
        $originalCode = (string) $invoice->party_code_snapshot;

        DB::table('customers')->where('id', $customerId)->update([
            'code' => 'CHANGED-CODE',
            'name' => 'Changed customer name',
            'updated_at' => now(),
        ]);

        $this->withTenantExecutionContext($tenantId, fn () => $invoice->refresh());
        $this->assertSame($originalCode, (string) $invoice->party_code_snapshot);
        $this->assertSame($originalName, (string) $invoice->party_name_snapshot);
    }

    public function test_reusing_an_idempotency_key_with_different_input_fails(): void
    {
        $tenantId = $this->createTenant();
        $organizationUnitId = $this->createOrganizationUnit($tenantId);
        $customerId = $this->createCustomer($tenantId, $organizationUnitId);
        $service = app(ManualInvoiceService::class);

        $this->withTenantExecutionContext($tenantId, fn () => $service->create(
            $this->invoiceData($tenantId, $organizationUnitId, $customerId, '100.000000'),
            'manual-invoice-request-2',
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Idempotency key was already used for a different request payload.');

        $this->withTenantExecutionContext($tenantId, fn () => $service->create(
            $this->invoiceData($tenantId, $organizationUnitId, $customerId, '200.000000'),
            'manual-invoice-request-2',
        ));
    }

    private function invoiceData(int $tenantId, int $organizationUnitId, int $customerId, string $unitPrice): ManualInvoiceData
    {
        return new ManualInvoiceData(
            tenantId: $tenantId,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-29',
            organizationUnitId: $organizationUnitId,
            customerId: $customerId,
            lines: [
                new ManualInvoiceLineData(
                    description: 'Manual service',
                    quantity: '1.000000',
                    unitPrice: $unitPrice,
                ),
            ],
        );
    }

    private function createCustomer(int $tenantId, int $organizationUnitId): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix,
            'name' => 'Invoice Customer '.$suffix,
            'customer_type' => 'individual',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrganizationUnit(int $tenantId): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Invoice Test Unit',
            'code' => 'INV-TEST',
            'depth' => 0,
            'is_active' => true,
            '_lft' => 0,
            '_rgt' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
