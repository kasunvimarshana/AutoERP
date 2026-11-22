<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCategory;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerBlockerService;
use Modules\Customer\Services\CustomerCategoryService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class CustomerBlockerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $this->mock(CustomerAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_customer_cannot_be_deleted_when_referenced_by_sales(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org '.$suffix,
                'code' => 'ORG-'.$suffix,
            ]);

            $warehouseId = DB::table('warehouses')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Warehouse '.$suffix,
                'code' => 'WH-'.$suffix,
                'type' => 'standard',
                'is_active' => true,
                'is_default' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $customer = Customer::query()->create([
                'tenant_id' => $tenantId,
                'customer_number' => 'CN-'.$suffix,
                'customer_type' => 'individual',
                'name' => 'Sales Customer',
                'code' => 'CUS-'.$suffix,
                'status' => 'active',
            ]);

            DB::table('sales')->insert([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->getKey(),
                'warehouse_id' => $warehouseId,
                'sale_number' => 'SALE-'.$suffix,
                'sale_date' => '2026-11-22',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Customer is referenced by business history and cannot be deleted.');

            app(CustomerBlockerService::class)->delete($customer);
        });
    }

    public function test_customer_cannot_be_deleted_when_referenced_by_rental_agreements(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Rental '.$suffix,
                'code' => 'ORGR-'.$suffix,
            ]);

            $customer = Customer::query()->create([
                'tenant_id' => $tenantId,
                'customer_number' => 'CN-RENT-'.$suffix,
                'customer_type' => 'individual',
                'name' => 'Rental Customer',
                'code' => 'CUS-RENT-'.$suffix,
                'status' => 'active',
            ]);

            $currencyId = CurrencyFixture::create([
                'name' => 'Rental Currency '.$suffix,
                'symbol' => '$',
            ]);

            DB::table('vehicle_rental_customer_agreements')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'reference' => 'VRCA-'.$suffix,
                'customer_id' => $customer->getKey(),
                'party_name_snapshot' => 'Rental Customer',
                'currency_id' => $currencyId,
                'currency_code_snapshot' => 'USD',
                'agreed_on' => '2026-11-22',
                'starts_on' => '2026-11-22',
                'basis' => 'daily',
                'driver_mode' => 'self_drive',
                'status' => 'draft',
                'terms' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Customer is referenced by business history and cannot be deleted.');

            app(CustomerBlockerService::class)->delete($customer);
        });
    }

    public function test_customer_category_cannot_be_deleted_when_child_category_exists(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $parent = CustomerCategory::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'CAT-PARENT',
                'name' => 'Parent Category',
            ]);

            $child = CustomerCategory::query()->create([
                'tenant_id' => $tenantId,
                'parent_id' => $parent->getKey(),
                'code' => 'CAT-CHILD',
                'name' => 'Child Category',
            ]);

            $service = app(CustomerCategoryService::class);

            try {
                $service->delete($parent);
                $this->fail('Expected parent category deletion to fail while child exists.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('child categories reference it', $e->getMessage());
            }

            // Child can be deleted
            $service->delete($child);
            $this->assertSoftDeleted('customer_categories', ['id' => $child->getKey()]);

            // Parent can now be deleted
            $service->delete($parent);
            $this->assertSoftDeleted('customer_categories', ['id' => $parent->getKey()]);
        });
    }

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Customer Test Tenant',
            'code' => 'cust-test-'.$suffix,
            'slug' => 'customer-test-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
