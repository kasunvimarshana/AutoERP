<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CustomerCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_customer_crud_works_without_automatic_user_access(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $createResponse = $this->withHeaders($headers)->postJson('/api/customer/customers', [
            'customer_code' => 'CUS-TST-001',
            'customer_name' => 'Acme Fleet Services',
            'customer_type' => 'Fleet operations',
            'metadata' => ['contact_person' => 'Nimal Perera'],
            'phone' => '+94770000001',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.customer_code', 'CUS-TST-001')
            ->assertJsonPath('data.customer_name', 'Acme Fleet Services');

        $customerId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_code' => 'CUS-TST-001',
        ]);
        $this->assertDatabaseMissing('customer_user_accounts', ['customer_id' => $customerId]);

        $this->withHeaders($headers)
            ->getJson('/api/customer/customers?search=Acme')
            ->assertOk()
            ->assertJsonPath('data.0.id', $customerId);

        $this->withHeaders($headers)
            ->getJson('/api/customer/customers/'.$customerId)
            ->assertOk()
            ->assertJsonPath('data.id', $customerId);

        $this->withHeaders($headers)
            ->putJson('/api/customer/customers/'.$customerId, [
                'customer_name' => 'Acme Fleet Services Updated',
                'customer_type' => 'Fleet operations',
                'metadata' => ['contact_person' => 'Nimal Perera'],
            ])
            ->assertOk()
            ->assertJsonPath('data.customer_name', 'Acme Fleet Services Updated');

        $this->withHeaders($headers)
            ->patchJson('/api/customer/customers/'.$customerId.'/status', ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withHeaders($headers)
            ->postJson('/api/customer/customer-contacts', [
                'customer_id' => $customerId,
                'contact_name' => 'Nimal Perera',
                'designation' => 'Fleet Manager',
                'phone' => '+94770000002',
                'is_primary' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.contact_name', 'Nimal Perera');

        $this->withHeaders($headers)
            ->getJson('/api/customer/customer-contacts?customer_id='.$customerId)
            ->assertOk()
            ->assertJsonPath('data.0.contact_name', 'Nimal Perera');

        $this->withHeaders($headers)
            ->postJson('/api/customer/customer-addresses', [
                'customer_id' => $customerId,
                'address_type' => 'billing',
                'address_line_1' => 'No 15, Station Road',
                'city' => 'Colombo',
                'postal_code' => '01000',
                'country_name' => 'Sri Lanka',
                'is_primary' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.address_line_1', 'No 15, Station Road');

        $this->withHeaders($headers)
            ->getJson('/api/customer/customer-addresses?customer_id='.$customerId)
            ->assertOk()
            ->assertJsonPath('data.0.address_line_1', 'No 15, Station Road');
    }

    public function test_customer_create_returns_validation_errors(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/customer/customers', ['customer_code' => 'CUS-TST-VAL'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_name']);
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
