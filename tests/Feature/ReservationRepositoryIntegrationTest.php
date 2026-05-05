<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Reservation\Domain\Entities\Reservation;
use Modules\Reservation\Domain\RepositoryInterfaces\ReservationRepositoryInterface;
use Modules\Reservation\Domain\ValueObjects\ReservationStatus;
use Modules\Reservation\Infrastructure\Persistence\Eloquent\Repositories\EloquentReservationRepository;
use Tests\TestCase;

class ReservationRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ReservationRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentReservationRepository();
        $this->seedTenant(911);
        $this->seedTenant(912);
    }

    public function test_reservation_crud_tenant_isolation_and_status_updates(): void
    {
        $now = new DateTimeImmutable();

        $reservationA = $this->makeReservation(
            id: 'eeeeeeee-0000-0000-0000-000000000001',
            tenantId: '911',
            orgUnitId: '911',
            reservationNumber: 'RSV-911-001',
            vehicleId: '11111111-1111-1111-1111-111111111111',
            customerId: '22222222-2222-2222-2222-222222222222',
            now: $now,
        );
        $savedA = $this->repository->save($reservationA);

        $reservationB = $this->makeReservation(
            id: 'eeeeeeee-0000-0000-0000-000000000002',
            tenantId: '912',
            orgUnitId: '912',
            reservationNumber: 'RSV-912-001',
            vehicleId: '33333333-3333-3333-3333-333333333333',
            customerId: '44444444-4444-4444-4444-444444444444',
            now: $now,
        );
        $this->repository->save($reservationB);

        $this->assertNotNull($this->repository->findById($savedA->id));

        $byTenant = $this->repository->findByTenant('911', '911');
        $this->assertCount(1, $byTenant);
        $this->assertSame('RSV-911-001', $byTenant[0]->reservationNumber);

        $byVehicle = $this->repository->findByVehicle('911', '11111111-1111-1111-1111-111111111111');
        $this->assertCount(1, $byVehicle);

        $before = $this->repository->findById($savedA->id);
        $after = $this->repository->updateStatus($savedA->id, ReservationStatus::Confirmed->value);
        $this->assertSame(ReservationStatus::Confirmed, $after->status);
        $this->assertGreaterThan($before->rowVersion, $after->rowVersion);

        $this->repository->delete($savedA->id);
        $this->assertNull($this->repository->findById($savedA->id));
    }

    private function makeReservation(
        string $id,
        string $tenantId,
        string $orgUnitId,
        string $reservationNumber,
        string $vehicleId,
        string $customerId,
        DateTimeImmutable $now,
    ): Reservation {
        return new Reservation(
            id: $id,
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            rowVersion: 1,
            reservationNumber: $reservationNumber,
            vehicleId: $vehicleId,
            customerId: $customerId,
            reservedFrom: $now,
            reservedTo: $now->modify('+2 days'),
            status: ReservationStatus::Pending,
            estimatedAmount: '10000.000000',
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
