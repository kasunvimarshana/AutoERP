<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Finance\Application\Contracts\PostPaymentServiceInterface;
use Modules\Finance\Application\Contracts\VoidPaymentServiceInterface;
use Modules\Finance\Domain\Exceptions\PaymentNotFoundException;
use Tests\TestCase;

class FinanceMutationTenantIsolationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSharedReferenceData();
        $this->seedTenantFinanceReferences(1, 101, 201);
        $this->seedTenantFinanceReferences(2, 102, 202);
    }

    public function testPostPaymentServiceRejectsCrossTenantMutation(): void
    {
        $createService = app(CreatePaymentServiceInterface::class);
        $postService = app(PostPaymentServiceInterface::class);

        app()->instance('current_tenant_id', 1);

        $created = $createService->execute($this->makePayload(
            tenantId: 1,
            paymentMethodId: 101,
            accountId: 201,
            paymentNumber: 'PAY-ISO-POST-001',
        ));

        app()->instance('current_tenant_id', 2);

        try {
            $postService->execute(['id' => $created->getId()]);
            $this->fail('Expected cross-tenant post mutation to be rejected.');
        } catch (PaymentNotFoundException) {
            $this->assertDatabaseHas('payments', [
                'id' => $created->getId(),
                'tenant_id' => 1,
                'payment_number' => 'PAY-ISO-POST-001',
                'status' => 'draft',
            ]);
        }
    }

    public function testVoidPaymentServiceRejectsCrossTenantMutation(): void
    {
        $createService = app(CreatePaymentServiceInterface::class);
        $voidService = app(VoidPaymentServiceInterface::class);

        app()->instance('current_tenant_id', 1);

        $created = $createService->execute($this->makePayload(
            tenantId: 1,
            paymentMethodId: 101,
            accountId: 201,
            paymentNumber: 'PAY-ISO-VOID-001',
        ));

        app()->instance('current_tenant_id', 2);

        try {
            $voidService->execute(['id' => $created->getId()]);
            $this->fail('Expected cross-tenant void mutation to be rejected.');
        } catch (PaymentNotFoundException) {
            $this->assertDatabaseHas('payments', [
                'id' => $created->getId(),
                'tenant_id' => 1,
                'payment_number' => 'PAY-ISO-VOID-001',
                'status' => 'draft',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function makePayload(
        int $tenantId,
        int $paymentMethodId,
        int $accountId,
        string $paymentNumber,
    ): array {
        return [
            'tenant_id' => $tenantId,
            'payment_number' => $paymentNumber,
            'direction' => 'outbound',
            'party_type' => 'supplier',
            'party_id' => 5001,
            'payment_method_id' => $paymentMethodId,
            'account_id' => $accountId,
            'amount' => 250.50,
            'currency_id' => 1,
            'payment_date' => '2026-05-02',
            'exchange_rate' => 1.0,
            'base_amount' => 250.50,
            'status' => 'draft',
            'reference' => 'TENANT-ISO',
            'notes' => 'Finance tenant mutation isolation',
            'idempotency_key' => null,
            'journal_entry_id' => null,
        ];
    }

    private function seedSharedReferenceData(): void
    {
        DB::table('currencies')->insert([
            'id' => 1,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTenantFinanceReferences(int $tenantId, int $paymentMethodId, int $accountId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant ' . $tenantId,
            'slug' => 'tenant-' . $tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('accounts')->insert([
            'id' => $accountId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'parent_id' => null,
            'code' => '1000-' . $tenantId,
            'name' => 'Cash ' . $tenantId,
            'type' => 'asset',
            'sub_type' => 'cash',
            'normal_balance' => 'debit',
            'is_system' => false,
            'is_bank_account' => true,
            'is_credit_card' => false,
            'currency_id' => 1,
            'description' => 'Seed account',
            'is_active' => true,
            'path' => null,
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('payment_methods')->insert([
            'id' => $paymentMethodId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'name' => 'Bank Transfer ' . $tenantId,
            'type' => 'bank_transfer',
            'account_id' => $accountId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
