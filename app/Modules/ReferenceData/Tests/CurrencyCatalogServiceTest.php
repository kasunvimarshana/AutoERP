<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\ReferenceData\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\ReferenceData\Services\CurrencyCatalogService;
use Tests\Support\CurrencyFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class CurrencyCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivating_base_currency_of_active_tenant_is_rejected(): void
    {
        $currencyId = CurrencyFixture::create(['name' => 'Tenant Base Currency 1']);
        $tenantId = $this->createTenant($currencyId);

        $service = app(CurrencyCatalogService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This currency is the base currency of an active tenant and cannot be deactivated.');

        $currency = CurrencyModel::query()->findOrFail($currencyId);
        $service->setActive($currencyId, (int) $currency->row_version, false);
    }

    public function test_deactivating_currency_with_open_unpaid_invoices_is_rejected(): void
    {
        $currencyId = CurrencyFixture::create(['name' => 'Open Invoice Currency']);
        $baseCurrencyId = CurrencyFixture::create(['name' => 'Base Currency']);
        $tenantId = $this->createTenant($baseCurrencyId);

        DB::table('invoices')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'invoice_number' => 'INV-TEST-CURR-1',
            'invoice_type' => 'manual',
            'direction' => 'outbound',
            'invoice_date' => '2026-06-01',
            'currency_id' => $currencyId,
            'exchange_rate' => '300.000000',
            'status' => 'posted',
            'grand_total' => '100.000000',
            'balance_due' => '100.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(CurrencyCatalogService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This currency is used by open unpaid invoices and cannot be deactivated.');

        $currency = CurrencyModel::query()->findOrFail($currencyId);
        $service->setActive($currencyId, (int) $currency->row_version, false);
    }

    public function test_deactivating_currency_assigned_to_active_customer_is_rejected(): void
    {
        $currencyId = CurrencyFixture::create(['name' => 'Customer Currency']);
        $baseCurrencyId = CurrencyFixture::create(['name' => 'Base Currency']);
        $tenantId = $this->createTenant($baseCurrencyId);

        $suffix = Str::upper(Str::random(5));
        DB::table('customers')->insert([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix,
            'name' => 'Customer '.$suffix,
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(CurrencyCatalogService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This currency is assigned as default currency for active customers and cannot be deactivated.');

        $currency = CurrencyModel::query()->findOrFail($currencyId);
        $service->setActive($currencyId, (int) $currency->row_version, false);
    }

    public function test_deactivating_currency_assigned_to_active_supplier_is_rejected(): void
    {
        $currencyId = CurrencyFixture::create(['name' => 'Supplier Currency']);
        $baseCurrencyId = CurrencyFixture::create(['name' => 'Base Currency']);
        $tenantId = $this->createTenant($baseCurrencyId);

        $suffix = Str::upper(Str::random(5));
        DB::table('suppliers')->insert([
            'tenant_id' => $tenantId,
            'supplier_number' => 'SUP-'.$suffix,
            'code' => 'SUP-'.$suffix,
            'name' => 'Supplier '.$suffix,
            'supplier_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(CurrencyCatalogService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This currency is assigned as default currency for active suppliers and cannot be deactivated.');

        $currency = CurrencyModel::query()->findOrFail($currencyId);
        $service->setActive($currencyId, (int) $currency->row_version, false);
    }

    public function test_unused_currency_can_be_deactivated_and_reactivated(): void
    {
        $currencyId = CurrencyFixture::create(['name' => 'Unused Currency']);
        $tenantId = $this->createTenant();
        $userId = TenantUserFixture::create(['tenant_id' => $tenantId, 'email' => 'unused-currency-test@example.com']);
        $service = app(CurrencyCatalogService::class);

        $currency = CurrencyModel::query()->findOrFail($currencyId);
        $this->assertTrue((bool) $currency->is_active);

        $updated = $this->withTenantRequestContext(
            $tenantId,
            $userId,
            fn () => $service->setActive($currencyId, (int) $currency->row_version, false),
        );
        $this->assertFalse((bool) $updated->is_active);

        $reactivated = $this->withTenantRequestContext(
            $tenantId,
            $userId,
            fn () => $service->setActive($currencyId, (int) $updated->row_version, true),
        );
        $this->assertTrue((bool) $reactivated->is_active);
    }

    private function createTenant(?int $baseCurrencyId = null): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'base_currency_id' => $baseCurrencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
