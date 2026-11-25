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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceBalanceUpdater;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceStatusService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class InvoiceSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_and_reverses_payment_allocations_through_invoice_owned_service(): void
    {
        $tenantId = $this->createTenant();
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
        $postingPlan = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-06',
            FinanceAccountRoleCode::Revenue,
            '1000.000000',
            description: 'Settlement invoice',
        );
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-SETTLE-CONTRACT',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Settlement invoice',
                    quantity: '1.000000',
                    unitPrice: '1000.000000',
                ),
            ],
            postingPlan: $postingPlan,
        )));

        $statuses = app(InvoiceStatusService::class);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => $statuses->transition($invoice, InvoiceStatus::Approved));
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => $statuses->transition($invoice, InvoiceStatus::Posted));

        $settlements = app(InvoiceSettlementServiceInterface::class);
        $applied = $this->withTenantExecutionContext($tenantId, fn () => $settlements->applyPaymentAllocation((int) $invoice->getKey(), '400.000000'));

        $this->assertSame('1000.000000', $applied->balanceBefore);
        $this->assertSame('600.000000', $applied->balanceAfter);

        $reversed = $this->withTenantExecutionContext($tenantId, fn () => $settlements->reversePaymentAllocation((int) $invoice->getKey(), '400.000000'));

        $this->assertSame('600.000000', $reversed->balanceBefore);
        $this->assertSame('1000.000000', $reversed->balanceAfter);
    }

    public function test_credit_note_allocation_tracks_remaining_balance_and_prevents_overallocation(): void
    {
        $tenantId = $this->createTenant();
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);

        $postingPlan1 = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-06',
            FinanceAccountRoleCode::Revenue,
            '1000.000000',
            description: 'Sales invoice',
        );
        $salesInvoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-SALES-1',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Sales line',
                    quantity: '1.000000',
                    unitPrice: '1000.000000',
                ),
            ],
            postingPlan: $postingPlan1,
        )));

        $postingPlan2 = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-06',
            FinanceAccountRoleCode::Revenue,
            '500.000000',
            description: 'Credit note',
        );
        $creditNote = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Credit,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'CN-SALES-1',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Credit note rebate',
                    quantity: '1.000000',
                    unitPrice: '500.000000',
                ),
            ],
            postingPlan: $postingPlan2,
        )));

        $statuses = app(InvoiceStatusService::class);
        $this->withTenantExecutionContext($tenantId, function () use ($statuses, $salesInvoice, $creditNote) {
            $statuses->transition($salesInvoice, InvoiceStatus::Approved);
            $statuses->transition($salesInvoice, InvoiceStatus::Posted);
            $statuses->transition($creditNote, InvoiceStatus::Approved);
            $statuses->transition($creditNote, InvoiceStatus::Posted);
        });

        $balanceUpdater = app(InvoiceBalanceUpdater::class);

        $this->withTenantExecutionContext($tenantId, function () use ($balanceUpdater, $salesInvoice, $creditNote) {
            // 1. Partial allocation of 300 from 500 Credit Note
            $balanceUpdater->allocateCredit($salesInvoice, 'credit_note', $creditNote->id, '300.000000');

            $creditNote->refresh()->load('balance');
            $this->assertEqualsWithDelta(300.0, (float) $creditNote->balance->credit_allocated_amount, 0.01);
            $this->assertEqualsWithDelta(200.0, (float) $creditNote->balance->remaining_amount, 0.01);
            $this->assertSame(InvoiceStatus::PartiallyPaid, $creditNote->status);

            // 2. Over-allocation attempt: try to allocate 250 when only 200 remains
            $thrown = false;
            try {
                $balanceUpdater->allocateCredit($salesInvoice, 'credit_note', $creditNote->id, '250.000000');
            } catch (\InvalidArgumentException $e) {
                $thrown = true;
                $this->assertStringContainsString('Credit allocation cannot exceed credit note remaining balance', $e->getMessage());
            }
            $this->assertTrue($thrown, 'Expected InvalidArgumentException when allocating more credit than available.');

            // 3. Fully allocate the remaining 200
            $balanceUpdater->allocateCredit($salesInvoice, 'credit_note', $creditNote->id, '200.000000');

            $creditNote->refresh()->load('balance');
            $this->assertEqualsWithDelta(500.0, (float) $creditNote->balance->credit_allocated_amount, 0.01);
            $this->assertEqualsWithDelta(0.0, (float) $creditNote->balance->remaining_amount, 0.01);
            $this->assertSame(InvoiceStatus::Paid, $creditNote->status);
        });
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-ISS-'.$suffix,
            'name' => 'Invoice Settlement '.$suffix,
            'slug' => 'invoice-settlement-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
