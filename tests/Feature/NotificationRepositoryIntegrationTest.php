<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Domain\RepositoryInterfaces\NotificationRepositoryInterface;
use Modules\Notifications\Domain\ValueObjects\EntityType;
use Modules\Notifications\Domain\ValueObjects\NotificationChannel;
use Modules\Notifications\Domain\ValueObjects\NotificationStatus;
use Modules\Notifications\Domain\ValueObjects\NotificationType;
use Modules\Notifications\Domain\ValueObjects\RecipientType;
use Modules\Notifications\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationRepository;
use Tests\TestCase;
use DateTimeImmutable;
use Modules\Notifications\Domain\Entities\Notification;

class NotificationRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private NotificationRepositoryInterface $repository;
    private string $tenantId;
    private string $orgUnitId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentNotificationRepository();
        $this->tenantId   = '901';
        $this->orgUnitId  = '901';

        $this->seedTenant((int) $this->tenantId);
    }

    public function test_notification_crud_tenant_isolation_mark_read_and_soft_delete(): void
    {
        $tenantB  = '902';
        $orgUnitB = '902';
        $this->seedTenant((int) $tenantB);

        $now    = new DateTimeImmutable();
        $entityId = 'cccccccc-0000-0000-0000-000000000001'; // UUID for entity_id (FK-less, just a reference)

        // --- create ---
        $notification = $this->makeNotification(
            id:                 'dddddddd-0000-0000-0000-000000000001',
            tenantId:           $this->tenantId,
            orgUnitId:          $this->orgUnitId,
            number:             'NOTIF-001',
            type:               NotificationType::RentalOverdue,
            entityType:         EntityType::Rental,
            entityId:           $entityId,
            now:                $now,
        );

        $saved = $this->repository->save($notification);

        $this->assertSame('dddddddd-0000-0000-0000-000000000001', $saved->id);
        $this->assertSame('NOTIF-001', $saved->notificationNumber);
        $this->assertSame(NotificationType::RentalOverdue, $saved->notificationType);
        $this->assertSame(EntityType::Rental, $saved->entityType);
        $this->assertSame(NotificationStatus::Pending, $saved->status);

        // --- findById ---
        $found = $this->repository->findById($saved->id);
        $this->assertNotNull($found);
        $this->assertSame('NOTIF-001', $found->notificationNumber);

        // --- create second notification for tenant B ---
        $notifB = $this->makeNotification(
            id:                 'dddddddd-0000-0000-0000-000000000002',
            tenantId:           '902',
            orgUnitId:          '902',
            number:             'NOTIF-B-001',
            type:               NotificationType::ServiceDue,
            entityType:         EntityType::Vehicle,
            entityId:           null,
            now:                $now,
        );
        $this->repository->save($notifB);

        // --- findByTenant isolation ---
        $list = $this->repository->findByTenant($this->tenantId, $this->orgUnitId);
        $this->assertCount(1, $list);
        $this->assertSame('NOTIF-001', $list[0]->notificationNumber);

        // --- findByEntity ---
        $byEntity = $this->repository->findByEntity($this->tenantId, 'rental', $entityId);
        $this->assertCount(1, $byEntity);
        $this->assertSame('NOTIF-001', $byEntity[0]->notificationNumber);

        // --- findUnread ---
        $unread = $this->repository->findUnread($this->tenantId, $this->orgUnitId);
        $this->assertCount(1, $unread);

        // --- markRead increments row_version ---
        $original    = $this->repository->findById($saved->id);
        $afterRead   = $this->repository->markRead($saved->id);
        $this->assertSame(NotificationStatus::Read, $afterRead->status);
        $this->assertNotNull($afterRead->readAt);
        $this->assertGreaterThan($original->rowVersion, $afterRead->rowVersion);

        // --- unread list now empty ---
        $this->assertCount(0, $this->repository->findUnread($this->tenantId, $this->orgUnitId));

        // --- soft delete ---
        $this->repository->delete($saved->id);
        $this->assertNull($this->repository->findById($saved->id));
    }

    private function makeNotification(
        string $id,
        string $tenantId,
        string $orgUnitId,
        string $number,
        NotificationType $type,
        EntityType $entityType,
        ?string $entityId,
        DateTimeImmutable $now,
    ): Notification {
        return new Notification(
            id:                 $id,
            tenantId:           $tenantId,
            orgUnitId:          $orgUnitId,
            rowVersion:         1,
            notificationNumber: $number,
            notificationType:   $type,
            entityType:         $entityType,
            entityId:           $entityId,
            recipientType:      RecipientType::System,
            recipientId:        null,
            title:              'Test Notification ' . $number,
            message:            'Test message for ' . $number,
            channel:            NotificationChannel::InApp,
            status:             NotificationStatus::Pending,
            sentAt:             null,
            readAt:             null,
            failedReason:       null,
            metadata:           ['test' => true],
            isActive:           true,
            createdAt:          $now,
            updatedAt:          $now,
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
