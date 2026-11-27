<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceOrientation;
use Modules\Invoice\Enums\InvoicePaperSize;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Http\Controllers\InvoiceController;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;
use Modules\Invoice\Models\InvoiceCreditAllocation;
use Modules\Invoice\Models\InvoiceLayout;
use Modules\Invoice\Models\InvoiceSource;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceStatusService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\CurrencyFixture;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class InvoiceAuditAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));

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

    private function createCustomer(int $tenantId): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUST-AUDIT-'.$suffix,
            'code' => 'CUST-AUDIT-'.$suffix,
            'name' => 'Audit Hardening Customer '.$suffix,
            'display_name' => 'Audit Hardening Customer '.$suffix,
            'customer_type' => 'company',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDraftInvoice(int $tenantId, int $customerId, int $currencyId): Invoice
    {
        return $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-10',
            invoiceNumber: 'INV-AUDIT-DRAFT-'.uniqid(),
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $currencyId,
            exchangeRate: '1.000000',
            status: InvoiceStatus::Draft,
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Audit item line',
                    quantity: '2.000000',
                    unitPrice: '500.000000',
                ),
            ],
        )));
    }

    private function createPostedInvoice(int $tenantId, int $customerId, int $currencyId): Invoice
    {
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
        $postingPlan = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-10',
            FinanceAccountRoleCode::Revenue,
            '1000.000000',
            description: 'Audit posted invoice',
        );

        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-10',
            invoiceNumber: 'INV-AUDIT-POSTED-'.uniqid(),
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $currencyId,
            exchangeRate: '1.000000',
            status: InvoiceStatus::Draft,
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Audit item line',
                    quantity: '2.000000',
                    unitPrice: '500.000000',
                ),
            ],
            postingPlan: $postingPlan,
        )));

        $statusService = app(InvoiceStatusService::class);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => $statusService->transitionIfVersion(
            $invoice,
            InvoiceStatus::Approved,
            (int) $invoice->row_version,
        ));

        return $this->withTenantExecutionContext($tenantId, fn () => $statusService->transitionIfVersion(
            $invoice,
            InvoiceStatus::Posted,
            (int) $invoice->row_version,
        ));
    }

    public function test_posted_invoice_snapshots_and_exchange_rate_are_immutable(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createPostedInvoice($tenantId, $customerId, $currencyId);
        $this->assertSame(InvoiceStatus::Posted, $invoice->status);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Historical posted invoice financial attributes and party/currency snapshots are immutable in place. Use credit/debit notes or governed adjustments.');

        $this->withTenantExecutionContext($tenantId, function () use ($invoice): void {
            $invoice->party_name_snapshot = 'Tampered Customer Name';
            $invoice->save();
        });
    }

    public function test_posted_invoice_exchange_rate_is_immutable(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createPostedInvoice($tenantId, $customerId, $currencyId);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Historical posted invoice financial attributes and party/currency snapshots are immutable in place. Use credit/debit notes or governed adjustments.');

        $this->withTenantExecutionContext($tenantId, function () use ($invoice): void {
            $invoice->exchange_rate = '2.500000';
            $invoice->save();
        });
    }

    public function test_invoice_submodels_lifecycle_guardrails_prevent_updates_on_posted_invoices(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createPostedInvoice($tenantId, $customerId, $currencyId);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $invoice): void {
            $source = InvoiceSource::query()->forceCreate([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoice->getKey(),
                'source_type' => 'manual',
                'source_id' => 999,
                'source_document_number' => 'DOC-999',
                'source_document_date' => '2026-06-10',
                'source_subtotal' => '1000.000000',
                'source_adjustment_total' => '0.000000',
                'source_grand_total' => '1000.000000',
                'invoiced_amount' => '1000.000000',
                'allocated_adjustment_amount' => '0.000000',
            ]);

            try {
                $source->source_document_number = 'MUTATED-DOC';
                $source->save();
                $this->fail('Expected LogicException on InvoiceSource updating for posted invoice.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Invoice sources of non-draft invoices cannot be modified.', $e->getMessage());
            }

            try {
                $source->delete();
                $this->fail('Expected LogicException on InvoiceSource deleting for posted invoice.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Invoice sources of non-draft invoices cannot be deleted.', $e->getMessage());
            }

            $line = $invoice->lines()->first();
            $sourceLine = InvoiceSourceLine::query()->forceCreate([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoice->getKey(),
                'invoice_line_id' => $line?->getKey(),
                'source_type' => 'manual',
                'source_id' => 999,
                'source_line_type' => 'manual_line',
                'source_line_id' => 1,
                'source_quantity' => '2.000000',
                'invoiced_quantity' => '2.000000',
                'source_unit_price' => '500.000000',
                'source_line_total' => '1000.000000',
                'invoiced_line_total' => '1000.000000',
            ]);

            try {
                $sourceLine->source_quantity = '3.000000';
                $sourceLine->save();
                $this->fail('Expected LogicException on InvoiceSourceLine updating for posted invoice.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Invoice source lines of non-draft invoices cannot be modified.', $e->getMessage());
            }

            try {
                $sourceLine->delete();
                $this->fail('Expected LogicException on InvoiceSourceLine deleting for posted invoice.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Invoice source lines of non-draft invoices cannot be deleted.', $e->getMessage());
            }
        });
    }

    public function test_credit_allocations_are_strictly_immutable_and_cannot_be_deleted(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createDraftInvoice($tenantId, $customerId, $currencyId);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $invoice): void {
            $creditAllocation = InvoiceCreditAllocation::query()->forceCreate([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoice->getKey(),
                'credit_source_type' => 'manual',
                'credit_source_id' => 456,
                'invoice_total' => '1000.000000',
                'previously_allocated_amount' => '0.000000',
                'allocated_amount' => '100.000000',
                'remaining_invoice_balance' => '900.000000',
            ]);

            try {
                $creditAllocation->allocated_amount = '200.000000';
                $creditAllocation->save();
                $this->fail('Expected LogicException on InvoiceCreditAllocation update.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Invoice credit allocations are immutable financial records.', $e->getMessage());
            }

            try {
                $creditAllocation->delete();
                $this->fail('Expected LogicException on InvoiceCreditAllocation delete.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Invoice credit allocations cannot be deleted.', $e->getMessage());
            }
        });
    }

    public function test_invoice_balance_record_cannot_be_deleted_when_parent_invoice_is_posted(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createPostedInvoice($tenantId, $customerId, $currencyId);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invoice balance records of non-draft invoices cannot be deleted.');

        $this->withTenantExecutionContext($tenantId, function () use ($invoice): void {
            $balance = $invoice->fresh(['balance'])->balance;
            $this->assertNotNull($balance);
            $balance->delete();
        });
    }

    public function test_invoice_layout_cannot_be_deleted_if_it_is_system_default_or_assigned_to_invoices(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $customerId, $currencyId): void {
            $defaultLayout = InvoiceLayout::query()->forceCreate([
                'tenant_id' => $tenantId,
                'code' => 'SYSTEM-DEFAULT-LAYOUT',
                'name' => 'System Default Layout',
                'is_system' => true,
                'is_active' => true,
                'row_version' => 1,
            ]);

            try {
                $defaultLayout->delete();
                $this->fail('Expected LogicException when deleting system default layout.');
            } catch (LogicException $e) {
                $this->assertSame('System default invoice layouts are protected and cannot be deleted.', $e->getMessage());
            }

            $customLayout = InvoiceLayout::query()->forceCreate([
                'tenant_id' => $tenantId,
                'code' => 'ASSIGNED-CUSTOM-LAYOUT',
                'name' => 'Assigned Custom Layout',
                'is_system' => false,
                'is_active' => true,
                'row_version' => 1,
            ]);

            $invoice = $this->createDraftInvoice($tenantId, $customerId, $currencyId);
            $invoice->invoice_layout_id = (int) $customLayout->id;
            $invoice->save();

            try {
                $customLayout->delete();
                $this->fail('Expected LogicException when deleting layout assigned to invoice.');
            } catch (LogicException $e) {
                $this->assertSame('This layout cannot be deleted because it is assigned to existing invoices.', $e->getMessage());
            }
        });
    }

    public function test_get_payable_balances_for_party_excludes_reversed_invoices(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createPostedInvoice($tenantId, $customerId, $currencyId);
        $provider = app(InvoiceBalanceProviderInterface::class);

        $beforeBalances = $this->withTenantExecutionContext($tenantId, fn () => $provider->getPayableBalancesForParty(
            $tenantId,
            null,
            'customer',
            $customerId,
            $currencyId,
        ));
        $this->assertCount(1, $beforeBalances);

        // Manually update status to Reversed to simulate reversal
        $this->withTenantExecutionContext($tenantId, function () use ($invoice): void {
            $invoice->status = InvoiceStatus::Reversed;
            $invoice->save();
        });

        $afterBalances = $this->withTenantExecutionContext($tenantId, fn () => $provider->getPayableBalancesForParty(
            $tenantId,
            null,
            'customer',
            $customerId,
            $currencyId,
        ));
        $this->assertCount(0, $afterBalances);
    }

    public function test_settlement_service_guards_against_missing_balance(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createDraftInvoice($tenantId, $customerId, $currencyId);
        // Force remove balance record
        $this->withTenantExecutionContext($tenantId, fn () => InvoiceBalance::query()->where('invoice_id', $invoice->getKey())->delete());

        $service = app(InvoiceSettlementServiceInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice balance record is missing.');

        $this->withTenantExecutionContext($tenantId, fn () => $service->applyPaymentAllocation((int) $invoice->getKey(), '100.000000'));
    }

    public function test_assign_layout_enforces_optimistic_locking(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createDraftInvoice($tenantId, $customerId, $currencyId);

        $layout = $this->withTenantExecutionContext($tenantId, fn () => InvoiceLayout::query()->forceCreate([
            'tenant_id' => $tenantId,
            'code' => 'TEST-OPT-LAYOUT',
            'name' => 'Test Opt Layout',
            'is_active' => true,
            'row_version' => 1,
        ]));

        $controller = app(InvoiceController::class);

        $requestMismatch = Request::create('/api/v1/invoices/'.$invoice->id.'/layout', 'PATCH', [
            'invoice_layout_id' => $layout->id,
            'expected_version' => 99999,
        ]);
        $requestMismatch->attributes->set('current_tenant_id', $tenantId);

        $this->expectException(ConflictHttpException::class);
        $this->withTenantExecutionContext($tenantId, fn () => $controller->assignLayout($requestMismatch, (int) $invoice->id));
    }

    public function test_invoice_layout_normalizes_enums_and_strings_for_css_and_pdf(): void
    {
        $layout = new InvoiceLayout;
        $layout->paper_size = 'a4';
        $layout->orientation = 'portrait';

        $this->assertSame('layout-a4', $layout->cssClass());
        $this->assertSame('A4 portrait', $layout->cssPageSize());
        $this->assertSame('A4', $layout->pdfPaper());
        $this->assertSame('portrait', $layout->orientation);
        $this->assertFalse($layout->isCompact());

        $layout->paper_size = 'thermal_80mm';
        $this->assertTrue($layout->isCompact());
        $this->assertSame('80mm auto', $layout->cssPageSize());
        $this->assertSame('layout-thermal', $layout->cssClass());

        // Backed enums
        $layout->paper_size = InvoicePaperSize::A5;
        $layout->orientation = InvoiceOrientation::Landscape;
        $this->assertSame('layout-a5', $layout->cssClass());
        $this->assertSame('A5 landscape', $layout->cssPageSize());
        $this->assertSame('A5', $layout->pdfPaper());
    }

    public function test_assign_layout_guards_against_retired_source_module_invoices(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createDraftInvoice($tenantId, $customerId, $currencyId);
        DB::table('invoices')->where('id', $invoice->id)->update(['invoice_type' => InvoiceType::Rental->value]);

        $layout = $this->withTenantExecutionContext($tenantId, fn () => InvoiceLayout::query()->forceCreate([
            'tenant_id' => $tenantId,
            'code' => 'TEST-RENTAL-LAYOUT',
            'name' => 'Test Rental Layout',
            'is_active' => true,
            'row_version' => 1,
        ]));

        $controller = app(InvoiceController::class);
        $request = Request::create('/api/v1/invoices/'.$invoice->id.'/layout', 'PATCH', [
            'invoice_layout_id' => $layout->id,
        ]);
        $request->attributes->set('current_tenant_id', $tenantId);

        $this->expectException(ValidationException::class);
        $this->withTenantExecutionContext($tenantId, fn () => $controller->assignLayout($request, (int) $invoice->id));
    }

    public function test_assign_layout_guards_against_terminal_status_invoices(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createDraftInvoice($tenantId, $customerId, $currencyId);
        $statusService = app(InvoiceStatusService::class);
        $this->withTenantExecutionContext($tenantId, fn () => $statusService->transition($invoice, InvoiceStatus::Cancelled));

        $layout = $this->withTenantExecutionContext($tenantId, fn () => InvoiceLayout::query()->forceCreate([
            'tenant_id' => $tenantId,
            'code' => 'TEST-CANCELLED-LAYOUT',
            'name' => 'Test Cancelled Layout',
            'is_active' => true,
            'row_version' => 1,
        ]));

        $controller = app(InvoiceController::class);
        $request = Request::create('/api/v1/invoices/'.$invoice->id.'/layout', 'PATCH', [
            'invoice_layout_id' => $layout->id,
        ]);
        $request->attributes->set('current_tenant_id', $tenantId);

        $this->expectException(ValidationException::class);
        $this->withTenantExecutionContext($tenantId, fn () => $controller->assignLayout($request, (int) $invoice->id));
    }

    public function test_transition_to_cancelled_atomically_clears_balance_due_and_invoice_balance(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $customerId = $this->createCustomer($tenantId);

        $invoice = $this->createDraftInvoice($tenantId, $customerId, $currencyId);
        $this->assertSame('1000.000000', (string) $invoice->balance_due);
        $this->assertSame('1000.000000', (string) $invoice->balance->remaining_amount);

        $statusService = app(InvoiceStatusService::class);
        $this->withTenantExecutionContext($tenantId, fn () => $statusService->transition($invoice, InvoiceStatus::Cancelled));

        [$freshInvoice, $freshBalance] = $this->withTenantExecutionContext($tenantId, function () use ($invoice): array {
            return [$invoice->fresh(), $invoice->balance()->first()];
        });

        $this->assertSame('0.000000', (string) $freshInvoice->balance_due);
        $this->assertNotNull($freshBalance);
        $this->assertSame('0.000000', (string) $freshBalance->remaining_amount);
        $this->assertSame('cancelled', (string) $freshBalance->status->value);
    }
}
