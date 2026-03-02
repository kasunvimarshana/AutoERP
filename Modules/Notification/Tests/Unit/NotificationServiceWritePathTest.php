<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit;

use Modules\Notification\Application\DTOs\SendNotificationDTO;
use Modules\Notification\Application\Services\NotificationService;
use Modules\Notification\Domain\Contracts\NotificationRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for NotificationService write-path and template methods.
 *
 * createTemplate() and sendNotification() call DB::transaction() and
 * Eloquent::create() internally, which require a full Laravel bootstrap.
 * These tests validate method signatures and DTO field-mapping contracts
 * using pure PHP.
 */
class NotificationServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // createTemplate — method existence and signature
    // -------------------------------------------------------------------------

    public function test_notification_service_has_create_template_method(): void
    {
        $this->assertTrue(
            method_exists(NotificationService::class, 'createTemplate'),
            'NotificationService must expose a public createTemplate() method.'
        );
    }

    public function test_create_template_is_public(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'createTemplate');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_create_template_accepts_data_array(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'createTemplate');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // sendNotification — method existence and signature
    // -------------------------------------------------------------------------

    public function test_notification_service_has_send_notification_method(): void
    {
        $this->assertTrue(
            method_exists(NotificationService::class, 'sendNotification'),
            'NotificationService must expose a public sendNotification() method.'
        );
    }

    public function test_send_notification_accepts_dto(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'sendNotification');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(SendNotificationDTO::class, (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // listTemplates — delegation (no DB::transaction, safe to test directly)
    // -------------------------------------------------------------------------

    public function test_list_templates_delegates_to_repository_all(): void
    {
        $collection = new \Illuminate\Database\Eloquent\Collection();

        $repo = $this->createMock(NotificationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($collection);

        $service = new NotificationService($repo);
        $result  = $service->listTemplates();

        $this->assertSame($collection, $result);
    }

    // -------------------------------------------------------------------------
    // SendNotificationDTO — log payload mapping
    // -------------------------------------------------------------------------

    public function test_notification_log_payload_channel_from_dto(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'sms',
            'recipient' => '+94771234567',
        ]);

        $logPayload = [
            'channel'   => $dto->channel,
            'recipient' => $dto->recipient,
            'status'    => 'sent',
        ];

        $this->assertSame('sms', $logPayload['channel']);
        $this->assertSame('+94771234567', $logPayload['recipient']);
        $this->assertSame('sent', $logPayload['status']);
    }

    public function test_notification_log_payload_no_template_is_null(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'push',
            'recipient' => 'device-token-abc',
        ]);

        $logPayload = [
            'notification_template_id' => $dto->templateId,
            'channel'                  => $dto->channel,
            'status'                   => 'sent',
        ];

        $this->assertNull($logPayload['notification_template_id']);
    }

    public function test_notification_log_payload_metadata_from_dto(): void
    {
        $meta = ['source' => 'erp', 'module' => 'inventory'];

        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'email',
            'recipient' => 'user@example.com',
            'metadata'  => $meta,
        ]);

        $logPayload = [
            'channel'  => $dto->channel,
            'metadata' => $dto->metadata,
        ];

        $this->assertSame($meta, $logPayload['metadata']);
    }
}
