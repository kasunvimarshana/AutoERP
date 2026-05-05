<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Invoicing\Domain\Entities\Invoice;
use Modules\Invoicing\Domain\RepositoryInterfaces\InvoiceRepositoryInterface;
use Modules\Invoicing\Domain\ValueObjects\InvoiceEntityType;
use Modules\Invoicing\Domain\ValueObjects\InvoiceStatus;
use Modules\Invoicing\Domain\ValueObjects\InvoiceType;
use Modules\Invoicing\Infrastructure\Persistence\Eloquent\Repositories\EloquentInvoiceRepository;
use Tests\TestCase;

class InvoiceRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentInvoiceRepository();
        $this->seedTenant(921);
        $this->seedTenant(922);
    }

    public function test_invoice_crud_tenant_isolation_status_and_payment(): void
    {
        $now = new DateTimeImmutable('2026-05-02');

        $invoiceA = $this->makeInvoice(
            id: 'ffffffff-0000-0000-0000-000000000001',
            tenantId: '921',
            orgUnitId: '921',
            number: 'INV-921-001',
            entityType: InvoiceEntityType::Rental,
            entityId: 'aaaaaaaa-1111-1111-1111-111111111111',
            now: $now,
        );
        $savedA = $this->repository->save($invoiceA);

        $invoiceB = $this->makeInvoice(
            id: 'ffffffff-0000-0000-0000-000000000002',
            tenantId: '922',
            orgUnitId: '922',
            number: 'INV-922-001',
            entityType: InvoiceEntityType::ServiceJob,
            entityId: 'bbbbbbbb-2222-2222-2222-222222222222',
            now: $now,
        );
        $this->repository->save($invoiceB);

        $this->assertNotNull($this->repository->findById($savedA->id));

        $byTenant = $this->repository->findByTenant('921', '921');
        $this->assertCount(1, $byTenant);
        $this->assertSame('INV-921-001', $byTenant[0]->invoiceNumber);

        $byEntity = $this->repository->findByEntity('921', 'rental', 'aaaaaaaa-1111-1111-1111-111111111111');
        $this->assertCount(1, $byEntity);

        $beforeStatus = $this->repository->findById($savedA->id);
        $afterStatus = $this->repository->updateStatus($savedA->id, InvoiceStatus::Issued->value);
        $this->assertSame(InvoiceStatus::Issued, $afterStatus->status);
        $this->assertGreaterThan($beforeStatus->rowVersion, $afterStatus->rowVersion);

        $afterPayment = $this->repository->recordPayment($savedA->id, '1000.000000');
        $this->assertSame('1000.000000', $afterPayment->paidAmount);
        $this->assertSame('11000.000000', $afterPayment->balanceAmount);

        $afterFullPayment = $this->repository->recordPayment($savedA->id, '11000.000000');
        $this->assertSame('12000.000000', $afterFullPayment->paidAmount);
        $this->assertSame('0.000000', $afterFullPayment->balanceAmount);
        $this->assertSame(InvoiceStatus::Paid, $afterFullPayment->status);

        $this->repository->delete($savedA->id);
        $this->assertNull($this->repository->findById($savedA->id));
    }

    private function makeInvoice(
        string $id,
        string $tenantId,
        string $orgUnitId,
        string $number,
        InvoiceEntityType $entityType,
        string $entityId,
        DateTimeImmutable $now,
    ): Invoice {
        return new Invoice(
            id: $id,
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            rowVersion: 1,
            invoiceNumber: $number,
            invoiceType: InvoiceType::Rental,
            entityType: $entityType,
            entityId: $entityId,
            status: InvoiceStatus::Draft,
            issueDate: $now,
            dueDate: $now->modify('+15 days'),
            subtotalAmount: '10000.000000',
            taxAmount: '2000.000000',
            totalAmount: '12000.000000',
            paidAmount: '0.000000',
            balanceAmount: '12000.000000',
            currency: 'USD',
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
