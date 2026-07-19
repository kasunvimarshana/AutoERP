<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\VehicleRental\Enums\VehicleFinanceAgreementStatus;
use Modules\VehicleRental\Models\VehicleFinanceAgreement;
use Modules\VehicleRental\Services\VehicleFinanceService;
use Tests\TestCase;

final class VehicleFinanceInvoiceClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_installment_payable_uses_vehicle_finance_invoice_type(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $supplierId = $this->createSupplier($tenantId, $currencyId);
        $vehicleId = $this->createVehicle($tenantId);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): VehicleFinanceAgreement => app(VehicleFinanceService::class)->create([
                'agreement_number' => 'VFA-CLASSIFICATION-001',
                'supplier_id' => $supplierId,
                'vehicle_id' => $vehicleId,
                'agreement_date' => '2026-07-01',
                'starts_at' => '2026-07-01',
                'matures_at' => '2027-07-01',
                'currency_id' => $currencyId,
                'principal_amount' => '1200000.000000',
                'initial_deposit_amount' => '200000.000000',
                'residual_value' => '0.000000',
                'interest_method' => 'flat',
                'annual_interest_rate' => '12.000000',
                'installment_frequency' => 'monthly',
                'installment_count' => 12,
                'payment_term_days' => 0,
            ], $tenantId, null, null),
        );
        $active = $this->withTenantExecutionContext(
            $tenantId,
            fn (): VehicleFinanceAgreement => app(VehicleFinanceService::class)->activate(
                $agreement,
                (int) $agreement->row_version,
                null,
            ),
        );
        self::assertSame(VehicleFinanceAgreementStatus::Active, $active->status);

        $installment = $active->installments->firstOrFail();
        $invoice = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(VehicleFinanceService::class)->createInstallmentPayable(
                $installment,
                (int) $installment->row_version,
                $installment->due_date->toDateString(),
                InvoiceStatus::Draft,
                null,
            ),
        );

        self::assertSame(InvoiceType::VehicleFinance, $invoice->invoice_type);
        self::assertSame('vehicle_finance_installment', $invoice->sources->sole()->source_type);
        $this->assertDatabaseMissing('invoices', [
            'id' => $invoice->getKey(),
            'invoice_type' => InvoiceType::Rental->value,
        ]);
    }

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => 'VFI',
            'name' => 'Vehicle Finance Invoice Currency',
            'symbol' => 'VFI',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenant(int $currencyId): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'VEHICLE-FINANCE-INVOICE',
            'name' => 'Vehicle Finance Invoice Tenant',
            'slug' => 'vehicle-finance-invoice-tenant',
            'base_currency_id' => $currencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSupplier(int $tenantId, int $currencyId): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_number' => 'SUP-VFI-001',
            'code' => 'SUP-VFI-001',
            'name' => 'Vehicle Finance Supplier',
            'supplier_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createVehicle(int $tenantId): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => 'VEH-VFI-001',
            'code' => 'VEH-VFI-001',
            'registration_number' => 'VFI-001',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
