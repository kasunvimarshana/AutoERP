<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PaymentCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_payment_crud_and_allocation_preview_work_with_real_backend_context(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $methodId = (int) DB::table('payment_methods')
            ->where('tenant_id', $tenantId)
            ->where('code', 'CASH')
            ->value('id');

        self::assertGreaterThan(0, $methodId, 'Payment bootstrap seed must provide CASH method.');

        $this->withHeaders($headers)
            ->getJson('/api/payment/payment-methods')
            ->assertOk()
            ->assertJsonFragment(['code' => 'CASH']);

        $createResponse = $this->withHeaders($headers)->postJson('/api/payment/payments', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_method_id' => $methodId,
            'payment_date' => now()->toDateString(),
            'direction' => 'inbound',
            'party_type' => 'external_party',
            'party_role' => 'payer',
            'payer_name' => 'Endpoint Test Payer',
            'amount' => 250,
            'reference' => 'PAY-FEATURE',
            'source_module' => 'shared',
            'source_type' => 'generic_reference',
            'source_id' => 2001,
            'source_reference' => 'PAY-SRC-2001',
            'metadata' => [
                'direction' => 'generic_receipt',
                'party_name' => 'Endpoint Test Payer',
            ],
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.amount', '250.0000')
            ->assertJsonPath('data.source_module', 'shared')
            ->assertJsonPath('data.source_type', 'generic_reference')
            ->assertJsonPath('data.source_id', 2001);

        $paymentId = (int) $createResponse->json('data.id');
        $paymentNumber = (string) $createResponse->json('data.payment_number');

        self::assertNotSame('', $paymentNumber);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_number' => $paymentNumber,
            'payer_name' => 'Endpoint Test Payer',
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/payment/payments/'.$paymentId)
            ->assertOk()
            ->assertJsonPath('data.id', $paymentId);

        $this->withHeaders($headers)
            ->putJson('/api/payment/payments/'.$paymentId, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'payment_method_id' => $methodId,
                'payment_date' => now()->toDateString(),
                'direction' => 'inbound',
                'party_type' => 'external_party',
                'party_role' => 'payer',
                'payer_name' => 'Endpoint Test Payer Updated',
                'amount' => 250,
                'reference' => 'PAY-FEATURE-UPD',
            ])
            ->assertOk()
            ->assertJsonPath('data.payer_name', 'Endpoint Test Payer Updated');

        $this->withHeaders($headers)
            ->postJson('/api/payment/payments/'.$paymentId.'/engines/preview-allocation', [
                'document_type' => 'generic_reference',
                'document_id' => 2001,
                'reference' => 'PAY-SRC-2001',
                'allocated_amount' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('data.payment_id', $paymentId)
            ->assertJsonPath('data.can_allocate', true)
            ->assertJsonPath('data.remaining_after_allocation', 150);

        $this->withHeaders($headers)
            ->postJson('/api/payment/payments/'.$paymentId.'/engines/post')
            ->assertOk();
        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'posted',
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/payment/payments/'.$paymentId.'/engines/refund', [
                'amount' => 25,
                'reference' => 'PAY-REFUND-TEST',
            ])
            ->assertOk();
        $this->assertDatabaseHas('payments', [
            'reversal_of_payment_id' => $paymentId,
            'reference' => 'PAY-REFUND-TEST',
        ]);

        $reverseSourceResponse = $this->withHeaders($headers)->postJson('/api/payment/payments', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_method_id' => $methodId,
            'payment_date' => now()->toDateString(),
            'direction' => 'inbound',
            'party_type' => 'external_party',
            'party_role' => 'payer',
            'payer_name' => 'Endpoint Reverse Payer',
            'amount' => 125,
            'reference' => 'PAY-REVERSE-SOURCE',
        ])->assertCreated();
        $reverseSourceId = (int) $reverseSourceResponse->json('data.id');

        $this->withHeaders($headers)
            ->postJson('/api/payment/payments/'.$reverseSourceId.'/engines/post')
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson('/api/payment/payments/'.$reverseSourceId.'/engines/reverse', [
                'reference' => 'PAY-REVERSAL-TEST',
            ])
            ->assertOk();
        $this->assertDatabaseHas('payments', [
            'id' => $reverseSourceId,
            'status' => 'reversed',
        ]);
        $this->assertDatabaseHas('payments', [
            'reversal_of_payment_id' => $reverseSourceId,
            'reference' => 'PAY-REVERSAL-TEST',
        ]);
    }

    public function test_payment_create_returns_validation_errors(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/payment/payments', ['amount' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id', 'payment_method_id', 'payment_date']);
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
