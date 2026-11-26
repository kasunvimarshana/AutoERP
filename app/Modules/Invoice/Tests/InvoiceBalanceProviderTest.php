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

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Tests\Support\CurrencyFixture;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class InvoiceBalanceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_invoice_balance_through_contract(): void
    {
        $tenantId = $this->createTenant();
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-BAL-CONTRACT',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Contract balance invoice',
                    quantity: '1.000000',
                    unitPrice: '1250.000000',
                ),
            ],
        )));

        $provider = app(InvoiceBalanceProviderInterface::class);

        $invoiceBalance = $this->withTenantExecutionContext($tenantId, fn () => $provider->getInvoiceBalance((int) $invoice->getKey()));
        $sharedBalance = $this->withTenantExecutionContext($tenantId, fn () => $provider->getBalance((int) $invoice->getKey()));

        $this->assertSame((int) $invoice->getKey(), $invoiceBalance->invoiceId);
        $this->assertSame('1250.000000', $invoiceBalance->invoiceTotal);
        $this->assertSame('1250.000000', $invoiceBalance->remainingAmount);
        $this->assertSame('1250.000000', $sharedBalance->remainingAmount);
        $this->assertSame('draft', $this->withTenantExecutionContext($tenantId, fn () => $provider->getInvoiceStatus((int) $invoice->getKey())));
    }

    public function test_payable_state_validation_is_scoped_before_balance_is_returned(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant();
        $customerId = $this->createCustomer($tenantId, null);
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
        $postingPlan = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-07',
            FinanceAccountRoleCode::Revenue,
            '750.000000',
            description: 'Scoped payable invoice',
        );
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-07',
            invoiceNumber: 'INV-BAL-SCOPED',
            partyType: 'customer',
            partyId: $customerId,
            status: InvoiceStatus::Posted,
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Scoped payable invoice',
                    quantity: '1.000000',
                    unitPrice: '750.000000',
                ),
            ],
            postingPlan: $postingPlan,
        )));

        $provider = app(InvoiceBalanceProviderInterface::class);

        $balance = $this->withTenantExecutionContext($tenantId, fn () => $provider->validatePayableState(
            invoiceId: (int) $invoice->getKey(),
            tenantId: $tenantId,
            organizationUnitId: null,
            partyType: 'customer',
            partyId: $customerId,
        ));

        $this->assertSame((int) $invoice->getKey(), $balance->sourceId);
        $this->assertSame('750.000000', $balance->remainingAmount);

        $this->expectException(ModelNotFoundException::class);

        $this->withTenantExecutionContext($tenantId, fn () => $provider->validatePayableState(
            invoiceId: (int) $invoice->getKey(),
            tenantId: $otherTenantId,
            organizationUnitId: null,
            partyType: 'customer',
            partyId: $customerId,
        ));
    }

    public function test_get_outstanding_totals_for_parties_computes_currency_and_base_amounts(): void
    {
        $baseCurrencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $usdCurrencyId = CurrencyFixture::create(['code' => 'USD', 'name' => 'US Dollar']);
        $tenantId = $this->createTenant();
        DB::table('tenants')->where('id', $tenantId)->update(['base_currency_id' => $baseCurrencyId]);
        $customerId = $this->createCustomer($tenantId, null);
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);

        $postingPlanUsd1 = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-07',
            FinanceAccountRoleCode::Revenue,
            '100.000000',
        );
        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-07',
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $usdCurrencyId,
            exchangeRate: '300.000000',
            status: InvoiceStatus::Posted,
            lines: [new InvoiceLineData(lineNumber: 1, description: 'USD line 1', quantity: '1.000000', unitPrice: '100.000000')],
            postingPlan: $postingPlanUsd1,
        )));

        $postingPlanUsd2 = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-08',
            FinanceAccountRoleCode::Revenue,
            '50.000000',
        );
        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-08',
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $usdCurrencyId,
            exchangeRate: '320.000000',
            status: InvoiceStatus::Posted,
            lines: [new InvoiceLineData(lineNumber: 1, description: 'USD line 2', quantity: '1.000000', unitPrice: '50.000000')],
            postingPlan: $postingPlanUsd2,
        )));

        $postingPlanBase = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-09',
            FinanceAccountRoleCode::Revenue,
            '20000.000000',
        );
        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-09',
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $baseCurrencyId,
            exchangeRate: '1.000000',
            status: InvoiceStatus::Posted,
            lines: [new InvoiceLineData(lineNumber: 1, description: 'Base line', quantity: '1.000000', unitPrice: '20000.000000')],
            postingPlan: $postingPlanBase,
        )));

        $provider = app(InvoiceBalanceProviderInterface::class);
        $totals = $this->withTenantExecutionContext($tenantId, fn () => $provider->getOutstandingTotalsForParties(
            $tenantId,
            null,
            'customer',
            [$customerId],
        ));

        $this->assertArrayHasKey($customerId, $totals);
        $customerTotals = collect($totals[$customerId])->keyBy('currency_code');

        $this->assertSame('150.000000', $customerTotals['USD']['amount']);
        $this->assertSame('46000.000000', $customerTotals['USD']['base_amount']);

        $this->assertSame('20000.000000', $customerTotals['LKR']['amount']);
        $this->assertSame('20000.000000', $customerTotals['LKR']['base_amount']);
    }

    public function test_get_party_exposure_in_currency_converts_mixed_currencies(): void
    {
        $baseCurrencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $usdCurrencyId = CurrencyFixture::create(['code' => 'USD', 'name' => 'US Dollar']);
        $eurCurrencyId = CurrencyFixture::create(['code' => 'EUR', 'name' => 'Euro']);
        $tenantId = $this->createTenant();
        DB::table('tenants')->where('id', $tenantId)->update(['base_currency_id' => $baseCurrencyId]);
        $customerId = $this->createCustomer($tenantId, null);
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);

        // USD 100 @ 300 LKR = 30,000 LKR
        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-07',
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $usdCurrencyId,
            exchangeRate: '300.000000',
            status: InvoiceStatus::Posted,
            lines: [new InvoiceLineData(lineNumber: 1, description: 'USD line', quantity: '1.000000', unitPrice: '100.000000')],
            postingPlan: app(InvoicePostingPlanFactory::class)->outbound(FinancePostingProfileCode::SalesInvoice, '2026-06-07', FinanceAccountRoleCode::Revenue, '100.000000'),
        )));

        // EUR 50 @ 360 LKR = 18,000 LKR
        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-08',
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $eurCurrencyId,
            exchangeRate: '360.000000',
            status: InvoiceStatus::Posted,
            lines: [new InvoiceLineData(lineNumber: 1, description: 'EUR line', quantity: '1.000000', unitPrice: '50.000000')],
            postingPlan: app(InvoicePostingPlanFactory::class)->outbound(FinancePostingProfileCode::SalesInvoice, '2026-06-08', FinanceAccountRoleCode::Revenue, '50.000000'),
        )));

        // LKR 20,000 @ 1.000000 = 20,000 LKR
        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-09',
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $baseCurrencyId,
            exchangeRate: '1.000000',
            status: InvoiceStatus::Posted,
            lines: [new InvoiceLineData(lineNumber: 1, description: 'LKR line', quantity: '1.000000', unitPrice: '20000.000000')],
            postingPlan: app(InvoicePostingPlanFactory::class)->outbound(FinancePostingProfileCode::SalesInvoice, '2026-06-09', FinanceAccountRoleCode::Revenue, '20000.000000'),
        )));

        $provider = app(InvoiceBalanceProviderInterface::class);

        // 1. In Base Currency (LKR): 30,000 + 18,000 + 20,000 = 68,000.000000
        $baseExposure = $this->withTenantExecutionContext($tenantId, fn () => $provider->getPartyExposureInCurrency(
            $tenantId,
            null,
            'customer',
            $customerId,
            $baseCurrencyId,
        ));

        $this->assertSame('LKR', $baseExposure['currency_code']);
        $this->assertSame('68000.000000', $baseExposure['total_exposure']);
        $this->assertSame('68000.000000', $baseExposure['total_base_exposure']);
        $this->assertSame([], $baseExposure['unconverted_currencies']);

        // 2. In USD (USD rate = 300.000000):
        // USD invoice is direct: 100.000000
        // EUR 18,000 base / 300 = 60.000000
        // LKR 20,000 base / 300 = 66.666667
        // Total = 100 + 60 + 66.666667 = 226.666667
        $usdExposure = $this->withTenantExecutionContext($tenantId, fn () => $provider->getPartyExposureInCurrency(
            $tenantId,
            null,
            'customer',
            $customerId,
            $usdCurrencyId,
            'USD',
            '300.000000',
        ));

        $this->assertSame('USD', $usdExposure['currency_code']);
        $this->assertSame('226.666666', $usdExposure['total_exposure']);
        $this->assertSame('68000.000000', $usdExposure['total_base_exposure']);
        $this->assertSame([], $usdExposure['unconverted_currencies']);

        // 3. In USD with omitted exchange rate: should resolve latest USD rate from invoices (300.000000)
        $autoResolvedExposure = $this->withTenantExecutionContext($tenantId, fn () => $provider->getPartyExposureInCurrency(
            $tenantId,
            null,
            'customer',
            $customerId,
            $usdCurrencyId,
        ));

        $this->assertSame('USD', $autoResolvedExposure['currency_code']);
        $this->assertSame('226.666666', $autoResolvedExposure['total_exposure']);
        $this->assertSame([], $autoResolvedExposure['unconverted_currencies']);
    }

    public function test_get_party_exposure_tracks_unconverted_currencies_when_rate_is_missing(): void
    {
        $baseCurrencyId = CurrencyFixture::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee']);
        $jpyCurrencyId = CurrencyFixture::create(['code' => 'JPY', 'name' => 'Japanese Yen']);
        $tenantId = $this->createTenant();
        DB::table('tenants')->where('id', $tenantId)->update(['base_currency_id' => $baseCurrencyId]);
        $customerId = $this->createCustomer($tenantId, null);
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);

        // Open invoice in base currency LKR 10,000
        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-07',
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $baseCurrencyId,
            exchangeRate: '1.000000',
            status: InvoiceStatus::Posted,
            lines: [new InvoiceLineData(lineNumber: 1, description: 'LKR line', quantity: '1.000000', unitPrice: '10000.000000')],
            postingPlan: app(InvoicePostingPlanFactory::class)->outbound(FinancePostingProfileCode::SalesInvoice, '2026-06-07', FinanceAccountRoleCode::Revenue, '10000.000000'),
        )));

        // Target currency is JPY, but no JPY invoice or exchange rate exists in the tenant
        $provider = app(InvoiceBalanceProviderInterface::class);
        $exposure = $this->withTenantExecutionContext($tenantId, fn () => $provider->getPartyExposureInCurrency(
            $tenantId,
            null,
            'customer',
            $customerId,
            $jpyCurrencyId,
        ));

        $this->assertSame('JPY', $exposure['currency_code']);
        $this->assertSame('0.000000', $exposure['total_exposure']);
        $this->assertSame('10000.000000', $exposure['total_base_exposure']);
        $this->assertContains('LKR', $exposure['unconverted_currencies']);
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-IBP-'.$suffix,
            'name' => 'Invoice Balance Provider '.$suffix,
            'slug' => 'invoice-balance-provider-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(int $tenantId, ?int $organizationUnitId): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUS-IBP-'.$suffix,
            'code' => 'CUS-IBP-'.$suffix,
            'name' => 'Invoice Balance Provider Customer '.$suffix,
            'legal_name' => 'Invoice Balance Provider Customer '.$suffix.' Legal',
            'display_name' => 'Invoice Balance Provider Customer '.$suffix,
            'customer_type' => 'company',
            'status' => 'active',
            'email' => 'invoice-balance-provider-customer-'.Str::lower($suffix).'@example.test',
            'phone' => '0111234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
