<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Supplier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierCategory;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\Supplier\Services\SupplierBlockerService;
use Modules\Supplier\Services\SupplierCategoryService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class SupplierBlockerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $this->mock(SupplierAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_supplier_cannot_be_deleted_when_referenced_by_rental_owner_agreements(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Owner '.$suffix,
                'code' => 'ORGO-'.$suffix,
            ]);

            $supplier = Supplier::query()->create([
                'tenant_id' => $tenantId,
                'supplier_number' => 'SN-'.$suffix,
                'supplier_type' => 'individual',
                'name' => 'Owner Supplier',
                'code' => 'SUP-OWNER-'.$suffix,
                'status' => 'active',
            ]);

            $currencyId = CurrencyFixture::create([
                'name' => 'Currency '.$suffix,
                'symbol' => '$',
            ]);

            $vehicleId = DB::table('vehicles')->insertGetId([
                'tenant_id' => $tenantId,
                'vehicle_number' => 'VN-'.$suffix,
                'registration_number' => 'REG-'.$suffix,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('vehicle_rental_owner_agreements')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'reference' => 'VROA-'.$suffix,
                'supplier_id' => $supplier->getKey(),
                'vehicle_id' => $vehicleId,
                'party_name_snapshot' => 'Owner Supplier',
                'vehicle_number_snapshot' => 'VN-'.$suffix,
                'vehicle_registration_snapshot' => 'REG-'.$suffix,
                'currency_id' => $currencyId,
                'currency_code_snapshot' => 'USD',
                'agreed_on' => '2026-11-22',
                'starts_on' => '2026-11-22',
                'basis' => 'monthly',
                'driver_mode' => 'self_drive',
                'status' => 'draft',
                'terms' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Supplier is referenced by business history and cannot be deleted.');

            app(SupplierBlockerService::class)->delete($supplier);
        });
    }

    public function test_supplier_cannot_be_deleted_when_referenced_by_rental_expenses(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Expense '.$suffix,
                'code' => 'ORGE-'.$suffix,
            ]);

            $supplier = Supplier::query()->create([
                'tenant_id' => $tenantId,
                'supplier_number' => 'SN-EXP-'.$suffix,
                'supplier_type' => 'individual',
                'name' => 'Expense Supplier',
                'code' => 'SUP-EXP-'.$suffix,
                'status' => 'active',
            ]);

            $currencyId = CurrencyFixture::create([
                'name' => 'Exp Currency '.$suffix,
                'symbol' => '$',
            ]);

            $vehicleId = DB::table('vehicles')->insertGetId([
                'tenant_id' => $tenantId,
                'vehicle_number' => 'VN-EXP-'.$suffix,
                'registration_number' => 'REG-EXP-'.$suffix,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('vehicle_rental_expenses')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expense_number' => 'EXP-'.$suffix,
                'vehicle_id' => $vehicleId,
                'vehicle_label_snapshot' => 'REG-EXP-'.$suffix,
                'supplier_id' => $supplier->getKey(),
                'expense_type' => 'fuel',
                'expense_date' => '2026-11-22',
                'currency_id' => $currencyId,
                'currency_code_snapshot' => 'USD',
                'net_amount' => 1500,
                'gross_amount' => 1500,
                'status' => 'approved',
                'fingerprint' => hash('sha256', 'fingerprint-'.$suffix),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Supplier is referenced by business history and cannot be deleted.');

            app(SupplierBlockerService::class)->delete($supplier);
        });
    }

    public function test_supplier_category_cannot_be_deleted_when_child_category_exists(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $parent = SupplierCategory::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'SUPCAT-P',
                'name' => 'Parent Supplier Category',
            ]);

            $child = SupplierCategory::query()->create([
                'tenant_id' => $tenantId,
                'parent_id' => $parent->getKey(),
                'code' => 'SUPCAT-C',
                'name' => 'Child Supplier Category',
            ]);

            $service = app(SupplierCategoryService::class);

            try {
                $service->delete($parent);
                $this->fail('Expected parent supplier category deletion to fail while child exists.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('child categories reference it', $e->getMessage());
            }

            // Child can be deleted
            $service->delete($child);
            $this->assertSoftDeleted('supplier_categories', ['id' => $child->getKey()]);

            // Parent can now be deleted
            $service->delete($parent);
            $this->assertSoftDeleted('supplier_categories', ['id' => $parent->getKey()]);
        });
    }

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Supplier Test Tenant',
            'code' => 'sup-test-'.$suffix,
            'slug' => 'supplier-test-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
