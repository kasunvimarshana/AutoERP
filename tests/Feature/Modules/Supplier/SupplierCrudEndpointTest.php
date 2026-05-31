<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Supplier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_supplier_crud_works_without_automatic_user_access(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $createResponse = $this->withHeaders($headers)->postJson('/api/supplier/suppliers', [
            'supplier_code' => 'SUP-TST-001',
            'supplier_name' => 'Metro Parts Supply',
            'supplier_type' => 'stock',
            'phone' => '+94771110001',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.supplier_code', 'SUP-TST-001')
            ->assertJsonPath('data.supplier_name', 'Metro Parts Supply');

        $supplierId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplierId,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_code' => 'SUP-TST-001',
        ]);
        $this->assertDatabaseMissing('supplier_user_accounts', ['supplier_id' => $supplierId]);

        $this->withHeaders($headers)
            ->getJson('/api/supplier/suppliers?search=Metro')
            ->assertOk()
            ->assertJsonPath('data.0.id', $supplierId);

        $this->withHeaders($headers)
            ->getJson('/api/supplier/suppliers/'.$supplierId)
            ->assertOk()
            ->assertJsonPath('data.id', $supplierId);

        $this->withHeaders($headers)
            ->putJson('/api/supplier/suppliers/'.$supplierId, [
                'supplier_name' => 'Metro Parts Supply Updated',
                'supplier_type' => 'stock',
            ])
            ->assertOk()
            ->assertJsonPath('data.supplier_name', 'Metro Parts Supply Updated');

        $this->withHeaders($headers)
            ->patchJson('/api/supplier/suppliers/'.$supplierId.'/status', ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withHeaders($headers)
            ->postJson('/api/supplier/supplier-contacts', [
                'supplier_id' => $supplierId,
                'name' => 'Accounts Desk',
                'designation' => 'Accounts Lead',
                'phone' => '+94771110002',
                'is_primary' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Accounts Desk');

        $this->withHeaders($headers)
            ->getJson('/api/supplier/supplier-contacts?supplier_id='.$supplierId)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Accounts Desk');

        $this->withHeaders($headers)
            ->postJson('/api/supplier/supplier-addresses', [
                'supplier_id' => $supplierId,
                'type' => 'billing',
                'address_line1' => 'No 21, Harbour Road',
                'city' => 'Colombo',
                'postal_code' => '01000',
                'is_default' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.address_line1', 'No 21, Harbour Road');

        $this->withHeaders($headers)
            ->getJson('/api/supplier/supplier-addresses?supplier_id='.$supplierId)
            ->assertOk()
            ->assertJsonPath('data.0.address_line1', 'No 21, Harbour Road');

        $this->withHeaders($headers)
            ->postJson('/api/supplier/suppliers/'.$supplierId.'/bank-accounts', [
                'account_name' => 'Metro Parts Supply',
                'account_number' => '001-000-7788',
                'bank_name' => 'Commercial Bank',
                'branch_name' => 'Colombo',
                'is_primary' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.account_name', 'Metro Parts Supply');

        $this->withHeaders($headers)
            ->getJson('/api/supplier/suppliers/'.$supplierId.'/bank-accounts')
            ->assertOk()
            ->assertJsonPath('data.0.account_number', '001-000-7788');
    }

    public function test_supplier_create_returns_validation_errors(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/supplier/suppliers', ['supplier_code' => 'SUP-TST-VAL'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supplier_name']);
    }

    /**
     * @return array{0:int,1:int,2:array<string,string>}
     */
    private function authenticatedHeaders(): array
    {
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        $loginResponse = $this->postJson('/api/auth/login', [
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'provider_key' => 'internal',
            'tenant_id' => $tenantId,
        ]);

        $loginResponse->assertOk();

        return [
            $tenantId,
            $organizationUnitId,
            [
                'Authorization' => 'Bearer '.(string) $loginResponse->json('data.tokens.access_token'),
                'X-Organization-Unit-ID' => (string) $organizationUnitId,
                'X-Tenant-ID' => (string) $tenantId,
            ],
        ];
    }
}
