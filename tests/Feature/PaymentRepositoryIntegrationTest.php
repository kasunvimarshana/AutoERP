<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Payments\Domain\ValueObjects\PaymentMethod;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Modules\Payments\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentRepository;
use Tests\TestCase;

class PaymentRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PaymentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentPaymentRepository();
        $this->seedTenant(931);
        $this->seedTenant(932);
    }

    public function testPaymentCrudTenantIsolationAndStatusTransition(): void
    {
        $now = new DateTimeImmutable('2026-05-02 10:00:00');

        $paymentA = $this->makePayment(
            id: 'abcdabcd-0000-0000-0000-000000000001',
            tenantId: '931',
            orgUnitId: '931',
            number: 'PAY-931-001',
            invoiceId: '99999999-1111-1111-1111-111111111111',
            amount: '5000.000000',
            now: $now,
        );
        $savedA = $this->repository->save($paymentA);

        $paymentB = $this->makePayment(
            id: 'abcdabcd-0000-0000-0000-000000000002',
            tenantId: '932',
            orgUnitId: '932',
            number: 'PAY-932-001',
            invoiceId: '99999999-2222-2222-2222-222222222222',
            amount: '9000.000000',
            now: $now,
        );
        $this->repository->save($paymentB);

        $this->assertNotNull($this->repository->findById('931', $savedA->id));

        $byTenant = $this->repository->findByTenant('931', '931');
        $this->assertCount(1, $byTenant);
        $this->assertSame('PAY-931-001', $byTenant[0]->paymentNumber);

        $byInvoice = $this->repository->findByInvoice('931', '99999999-1111-1111-1111-111111111111');
        $this->assertCount(1, $byInvoice);

        $before = $this->repository->findById('931', $savedA->id);
        $after = $this->repository->updateStatus('931', $savedA->id, PaymentStatus::Completed->value);
        $this->assertSame(PaymentStatus::Completed, $after->status);
        $this->assertNotNull($after->paidAt);
        $this->assertGreaterThan($before->rowVersion, $after->rowVersion);

        $this->repository->delete('931', $savedA->id);
        $this->assertNull($this->repository->findById('931', $savedA->id));
    }

    private function makePayment(
        string $id,
        string $tenantId,
        string $orgUnitId,
        string $number,
        string $invoiceId,
        string $amount,
        DateTimeImmutable $now,
    ): Payment {
        return new Payment(
            id: $id,
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            rowVersion: 1,
            paymentNumber: $number,
            invoiceId: $invoiceId,
            paymentMethod: PaymentMethod::Card,
            status: PaymentStatus::Pending,
            amount: $amount,
            currency: 'USD',
            paidAt: null,
            referenceNumber: null,
            notes: null,
            metadata: ['test' => true],
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    private function seedTenant(int $tenantId): void
    {
        if (DB::table('tenants')->where('id', $tenantId)->exists()) {
            return;
        }

        DB::table('tenants')->insert([
            'id'                   => $tenantId,
            'name'                 => 'Tenant ' . $tenantId,
            'slug'                 => 'tenant-' . $tenantId,
            'domain'               => null,
            'logo_path'            => null,
            'database_config'      => null,
            'mail_config'          => null,
            'cache_config'         => null,
            'queue_config'         => null,
            'feature_flags'        => null,
            'api_keys'             => null,
            'settings'             => null,
            'plan'                 => 'free',
            'tenant_plan_id'       => null,
            'status'               => 'active',
            'active'               => true,
            'trial_ends_at'        => null,
            'subscription_ends_at' => null,
            'created_at'           => now(),
            'updated_at'           => now(),
            'deleted_at'           => null,
        ]);
    }
}
