<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CustomerSupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $login = $this->postJson('/api/auth/login', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'provider_key' => 'internal',
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'Feature test',
        ])->assertOk();

        $this->headers = [
            'Authorization' => 'Bearer '.$login->json('data.tokens.access_token'),
            'X-Tenant-ID' => '1',
            'X-Organization-Unit-ID' => '1',
        ];
    }

    public function test_customer_crud_is_tenant_scoped_and_validated(): void
    {
        $created = $this->withHeaders($this->headers)
            ->postJson('/api/customer/customers', $this->customerPayload())
            ->assertCreated()
            ->assertJsonPath('data.customer_code', 'CUS-100')
            ->assertJsonPath('data.name', 'Acme Retail')
            ->assertJsonPath('data.address.city', 'Colombo')
            ->assertJsonPath('data.current_credit_balance', null)
            ->assertJsonPath('data.available_credit', null);

        $customerId = (int) $created->json('data.id');

        $this->withHeaders($this->headers)
            ->postJson('/api/customer/customers', $this->customerPayload())
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['errors' => ['customer_code']]);

        $this->withHeaders($this->headers)
            ->postJson('/api/customer/customers', [
                ...$this->customerPayload(),
                'customer_code' => 'CUS-NEGATIVE',
                'credit_limit' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['credit_limit']]);

        $this->withHeaders($this->headers)
            ->getJson('/api/customer/customers?search=Acme&status=active&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $this->withHeaders($this->headers)
            ->patchJson('/api/customer/customers/'.$customerId, [
                'name' => 'Acme Retail Updated',
                'status' => 'inactive',
                'credit_limit' => 18000.25,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Acme Retail Updated')
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.credit_limit', '18000.2500');

        $otherTenantId = $this->createOtherTenant();
        $foreignCustomerId = DB::table('customers')->insertGetId([
            'tenant_id' => $otherTenantId,
            'organization_unit_id' => null,
            'customer_code' => 'CUS-FOREIGN',
            'customer_name' => 'Foreign Customer',
            'customer_type' => 'business',
            'credit_limit' => 0,
            'payment_terms_days' => 0,
            'status' => 'active',
            'is_active' => true,
            'credit_hold' => false,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders($this->headers)
            ->getJson('/api/customer/customers/'.$foreignCustomerId)
            ->assertNotFound();

        $this->withHeaders($this->headers)
            ->deleteJson('/api/customer/customers/'.$customerId)
            ->assertNoContent();

        $this->assertSoftDeleted('customers', ['id' => $customerId, 'tenant_id' => 1]);
        $this->assertSoftDeleted('customer_addresses', ['customer_id' => $customerId, 'tenant_id' => 1]);
    }

    public function test_supplier_crud_persists_primary_address_and_status_history(): void
    {
        $created = $this->withHeaders($this->headers)
            ->postJson('/api/supplier/suppliers', $this->supplierPayload())
            ->assertCreated()
            ->assertJsonPath('data.supplier_code', 'SUP-100')
            ->assertJsonPath('data.name', 'Northwind Parts')
            ->assertJsonPath('data.address.country_name', 'Sri Lanka');

        $supplierId = (int) $created->json('data.id');

        $this->withHeaders($this->headers)
            ->getJson('/api/supplier/suppliers?search=Northwind&sort_by=supplier_code&sort_direction=desc')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 1);

        $this->withHeaders($this->headers)
            ->putJson('/api/supplier/suppliers/'.$supplierId, [
                ...$this->supplierPayload(),
                'name' => 'Northwind Parts Limited',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Northwind Parts Limited')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('supplier_status_histories', [
            'supplier_id' => $supplierId,
            'from_status' => 'active',
            'to_status' => 'inactive',
        ]);

        $this->withHeaders($this->headers)
            ->deleteJson('/api/supplier/suppliers/'.$supplierId)
            ->assertNoContent();

        $this->assertSoftDeleted('suppliers', ['id' => $supplierId, 'tenant_id' => 1]);
        $this->assertSoftDeleted('supplier_addresses', ['supplier_id' => $supplierId, 'tenant_id' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function customerPayload(): array
    {
        return [
            'customer_code' => 'CUS-100',
            'name' => 'Acme Retail',
            'display_name' => 'Acme',
            'email' => 'accounts@acme.test',
            'phone' => '+94 11 234 5678',
            'mobile' => '+94 77 123 4567',
            'tax_number' => 'TIN-CUS-100',
            'vat_number' => 'VAT-CUS-100',
            'credit_limit' => 15000.5,
            'payment_terms_days' => 30,
            'status' => 'active',
            'notes' => 'Priority customer.',
            'address' => [
                'label' => 'Head office',
                'address_line_1' => '100 Main Street',
                'address_line_2' => 'Floor 2',
                'city' => 'Colombo',
                'state_province' => 'Western',
                'postal_code' => '00100',
                'country_name' => 'Sri Lanka',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function supplierPayload(): array
    {
        return [
            'supplier_code' => 'SUP-100',
            'name' => 'Northwind Parts',
            'display_name' => 'Northwind',
            'email' => 'sales@northwind.test',
            'phone' => '+94 11 555 1212',
            'mobile' => '+94 76 555 1212',
            'tax_number' => 'TIN-SUP-100',
            'vat_number' => 'VAT-SUP-100',
            'credit_limit' => 25000,
            'payment_terms_days' => 45,
            'status' => 'active',
            'notes' => 'Preferred supplier.',
            'address' => [
                'label' => 'Main warehouse',
                'address_line_1' => '25 Supply Road',
                'city' => 'Colombo',
                'state_province' => 'Western',
                'postal_code' => '00200',
                'country_name' => 'Sri Lanka',
            ],
        ];
    }

    private function createOtherTenant(): int
    {
        $tenant = (array) DB::table('tenants')->where('id', 1)->first();
        unset($tenant['id']);

        $tenant['code'] = 'OTHER';
        $tenant['name'] = 'Other Tenant';
        $tenant['slug'] = 'other';
        $tenant['uuid'] = (string) Str::uuid();
        $tenant['isolation_key'] = 'other';
        $tenant['created_at'] = now();
        $tenant['updated_at'] = now();

        return (int) DB::table('tenants')->insertGetId($tenant);
    }
}
