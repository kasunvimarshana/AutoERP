<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Receipts\Domain\Entities\Receipt;
use Modules\Receipts\Domain\RepositoryInterfaces\ReceiptRepositoryInterface;
use Modules\Receipts\Domain\ValueObjects\ReceiptStatus;
use Modules\Receipts\Domain\ValueObjects\ReceiptType;
use Modules\Receipts\Infrastructure\Persistence\Eloquent\Repositories\EloquentReceiptRepository;
use Tests\TestCase;

class ReceiptRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ReceiptRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentReceiptRepository();
        $this->seedTenant(941);
        $this->seedTenant(942);
    }

    public function test_receipt_crud_tenant_isolation_and_status_update(): void
    {
        $now = new DateTimeImmutable('2026-05-02 12:00:00');

        $receiptA = $this->makeReceipt(
            id: 'dcba4321-0000-0000-0000-000000000001',
            tenantId: '941',
            orgUnitId: '941',
            receiptNumber: 'REC-941-001',
            paymentId: '12345678-1111-1111-1111-111111111111',
            amount: '7500.000000',
            now: $now,
        );
        $savedA = $this->repository->save($receiptA);

        $receiptB = $this->makeReceipt(
            id: 'dcba4321-0000-0000-0000-000000000002',
            tenantId: '942',
            orgUnitId: '942',
            receiptNumber: 'REC-942-001',
            paymentId: '12345678-2222-2222-2222-222222222222',
            amount: '9300.000000',
            now: $now,
        );
        $this->repository->save($receiptB);

        $this->assertNotNull($this->repository->findById($savedA->id));

        $byTenant = $this->repository->findByTenant('941', '941');
        $this->assertCount(1, $byTenant);
        $this->assertSame('REC-941-001', $byTenant[0]->receiptNumber);

        $byPayment = $this->repository->findByPayment('941', '12345678-1111-1111-1111-111111111111');
        $this->assertCount(1, $byPayment);

        $before = $this->repository->findById($savedA->id);
        $after = $this->repository->updateStatus($savedA->id, ReceiptStatus::Voided->value);
        $this->assertSame(ReceiptStatus::Voided, $after->status);
        $this->assertGreaterThan($before->rowVersion, $after->rowVersion);

        $this->repository->delete($savedA->id);
        $this->assertNull($this->repository->findById($savedA->id));
    }

    private function makeReceipt(
        string $id,
        string $tenantId,
        string $orgUnitId,
        string $receiptNumber,
        string $paymentId,
        string $amount,
        DateTimeImmutable $now,
    ): Receipt {
        return new Receipt(
            id: $id,
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            rowVersion: 1,
            receiptNumber: $receiptNumber,
            paymentId: $paymentId,
            invoiceId: null,
            receiptType: ReceiptType::Payment,
            status: ReceiptStatus::Issued,
            amount: $amount,
            currency: 'USD',
            issuedAt: $now,
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
