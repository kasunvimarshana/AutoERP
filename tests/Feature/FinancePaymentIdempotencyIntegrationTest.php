<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Tests\TestCase;

class FinancePaymentIdempotencyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSharedReferenceData();
        $this->seedTenantFinanceReferences(1, 101, 201);
        $this->seedTenantFinanceReferences(2, 102, 202);
    }

    public function test_payment_create_replay_returns_existing_row_for_same_tenant_and_idempotency_key(): void
    {
        $service = app(CreatePaymentServiceInterface::class);

        $first = $service->execute($this->makePayload(
            tenantId: 1,
            paymentMethodId: 101,
            accountId: 201,
            paymentNumber: 'PAY-00001',
            idempotencyKey: 'payment-replay-key-1',
        ));

        $second = $service->execute($this->makePayload(
            tenantId: 1,
            paymentMethodId: 101,
            accountId: 201,
            paymentNumber: 'PAY-REPLAY-SHOULD-NOT-PERSIST',
            idempotencyKey: 'payment-replay-key-1',
        ));

        $this->assertSame($first->getId(), $second->getId());
        $this->assertSame('PAY-00001', $second->getPaymentNumber());
        $this->assertSame(1, DB::table('payments')->where('tenant_id', 1)->count());
        $this->assertSame(1, DB::table('payments')->where('tenant_id', 1)->where('idempotency_key', 'payment-replay-key-1')->count());
    }

    public function test_payment_create_allows_same_idempotency_key_across_different_tenants(): void
    {
        $service = app(CreatePaymentServiceInterface::class);

        $tenantOnePayment = $service->execute($this->makePayload(
            tenantId: 1,
            paymentMethodId: 101,
            accountId: 201,
            paymentNumber: 'PAY-T1-00001',
            idempotencyKey: 'shared-cross-tenant-key',
        ));

        $tenantTwoPayment = $service->execute($this->makePayload(
            tenantId: 2,
            paymentMethodId: 102,
            accountId: 202,
            paymentNumber: 'PAY-T2-00001',
            idempotencyKey: 'shared-cross-tenant-key',
        ));

        $this->assertNotSame($tenantOnePayment->getId(), $tenantTwoPayment->getId());
        $this->assertSame(2, DB::table('payments')->where('idempotency_key', 'shared-cross-tenant-key')->count());
        $this->assertSame(1, DB::table('payments')->where('tenant_id', 1)->where('idempotency_key', 'shared-cross-tenant-key')->count());
        $this->assertSame(1, DB::table('payments')->where('tenant_id', 2)->where('idempotency_key', 'shared-cross-tenant-key')->count());
    }

    public function test_payment_create_without_idempotency_key_persists_distinct_rows(): void
    {
        $service = app(CreatePaymentServiceInterface::class);

        $first = $service->execute($this->makePayload(
            tenantId: 1,
            paymentMethodId: 101,
            accountId: 201,
            paymentNumber: 'PAY-NO-KEY-00001',
            idempotencyKey: null,
        ));

        $second = $service->execute($this->makePayload(
            tenantId: 1,
            paymentMethodId: 101,
            accountId: 201,
            paymentNumber: 'PAY-NO-KEY-00002',
            idempotencyKey: null,
        ));

        $this->assertNotSame($first->getId(), $second->getId());
        $this->assertSame(2, DB::table('payments')->where('tenant_id', 1)->whereNull('idempotency_key')->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function makePayload(
        int $tenantId,
        int $paymentMethodId,
        int $accountId,
        string $paymentNumber,
        ?string $idempotencyKey,
    ): array {
        return [
            'tenant_id' => $tenantId,
            'payment_number' => $paymentNumber,
            'direction' => 'outbound',
            'party_type' => 'supplier',
            'party_id' => 5001,
            'payment_method_id' => $paymentMethodId,
            'account_id' => $accountId,
            'amount' => 150.75,
            'currency_id' => 1,
            'payment_date' => '2026-04-28',
            'exchange_rate' => 1.0,
            'base_amount' => 150.75,
            'status' => 'draft',
            'reference' => 'INV-REPLAY-1001',
            'notes' => 'Integration replay coverage',
            'idempotency_key' => $idempotencyKey,
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
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
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
            'code' => '1000-'.$tenantId,
            'name' => 'Cash '.$tenantId,
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
            'name' => 'Bank Transfer '.$tenantId,
            'type' => 'bank_transfer',
            'account_id' => $accountId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
