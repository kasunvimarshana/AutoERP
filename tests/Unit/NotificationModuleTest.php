<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Domain\Entities\NotificationTemplate;
use Modules\Notification\Domain\Entities\NotificationPreference;
use Modules\Notification\Domain\ValueObjects\NotificationChannel;
use Modules\Notification\Domain\ValueObjects\NotificationStatus;
use Modules\Notification\Domain\Exceptions\NotificationNotFoundException;
use Modules\Notification\Domain\Exceptions\NotificationTemplateNotFoundException;
use Modules\Notification\Domain\Exceptions\InvalidNotificationException;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationRepositoryInterface;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationTemplateRepositoryInterface;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationPreferenceRepositoryInterface;
use Modules\Notification\Application\Services\NotificationService;
use Modules\Notification\Application\Services\NotificationTemplateService;
use Modules\Notification\Application\Services\NotificationPreferenceService;
use Modules\Notification\Application\Services\SendNotificationService;
use Modules\Notification\Infrastructure\Channels\NotificationChannelDispatcher;
use Modules\Notification\Infrastructure\Channels\NotificationChannelInterface;
use Modules\Notification\Infrastructure\Channels\Drivers\DatabaseChannelDriver;
use Modules\Notification\Infrastructure\Channels\Drivers\EmailChannelDriver;
use Modules\Notification\Infrastructure\Channels\Drivers\SmsChannelDriver;
use Modules\Notification\Infrastructure\Channels\Drivers\PushChannelDriver;

class NotificationModuleTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helper factories
    // ─────────────────────────────────────────────────────────────────────────

    private function makeNotification(
        int $id = 1,
        string $status = NotificationStatus::PENDING,
        ?string $readAt = null,
    ): Notification {
        return new Notification(
            $id,
            1,                            // tenantId
            42,                           // userId
            'order.created',             // type
            NotificationChannel::fromString('database'),
            'Order Created',             // title
            'Your order #1234 has been placed.',
            ['order_id' => 1234],        // data
            NotificationStatus::fromString($status),
            $readAt ? new \DateTime($readAt) : null,
            null,                        // sentAt
            new \DateTime(),             // createdAt
            new \DateTime(),             // updatedAt
        );
    }

    private function makeTemplate(int $id = 1, bool $active = true): NotificationTemplate
    {
        return new NotificationTemplate(
            $id,
            1,
            'order.created',
            'Order Created',
            'email',
            'Your order {{ order_id }} is confirmed',
            'Hi {{ customer_name }}, your order {{ order_id }} has been placed.',
            ['order_id', 'customer_name'],
            $active,
            new \DateTime(),
            new \DateTime(),
        );
    }

    private function makePreference(
        int $id = 1,
        bool $enabled = true,
    ): NotificationPreference {
        return new NotificationPreference(
            $id,
            1,
            42,
            'order.created',
            'email',
            $enabled,
            new \DateTime(),
            new \DateTime(),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NotificationChannel value object
    // ─────────────────────────────────────────────────────────────────────────

    public function test_channel_value_object_valid_values(): void
    {
        foreach (['database', 'email', 'sms', 'push'] as $ch) {
            $channel = NotificationChannel::fromString($ch);
            $this->assertEquals($ch, $channel->getValue());
            $this->assertEquals($ch, (string) $channel);
        }
    }

    public function test_channel_value_object_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        NotificationChannel::fromString('fax');
    }

    public function test_channel_all_returns_four_channels(): void
    {
        $this->assertCount(4, NotificationChannel::all());
    }

    public function test_channel_factory_methods(): void
    {
        $this->assertEquals('database', NotificationChannel::database()->getValue());
        $this->assertEquals('email',    NotificationChannel::email()->getValue());
        $this->assertEquals('sms',      NotificationChannel::sms()->getValue());
        $this->assertEquals('push',     NotificationChannel::push()->getValue());
    }

    public function test_channel_equals(): void
    {
        $a = NotificationChannel::fromString('email');
        $b = NotificationChannel::fromString('email');
        $c = NotificationChannel::fromString('sms');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NotificationStatus value object
    // ─────────────────────────────────────────────────────────────────────────

    public function test_status_value_object_valid_values(): void
    {
        foreach (['pending', 'sent', 'failed', 'read'] as $s) {
            $status = NotificationStatus::fromString($s);
            $this->assertEquals($s, $status->getValue());
        }
    }

    public function test_status_value_object_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        NotificationStatus::fromString('unknown');
    }

    public function test_status_predicates(): void
    {
        $this->assertTrue(NotificationStatus::pending()->isPending());
        $this->assertTrue(NotificationStatus::sent()->isSent());
        $this->assertTrue(NotificationStatus::failed()->isFailed());
        $this->assertTrue(NotificationStatus::read()->isRead());

        $this->assertFalse(NotificationStatus::pending()->isSent());
        $this->assertFalse(NotificationStatus::sent()->isPending());
    }

    public function test_status_equals(): void
    {
        $a = NotificationStatus::fromString('sent');
        $b = NotificationStatus::fromString('sent');
        $c = NotificationStatus::fromString('read');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Notification entity
    // ─────────────────────────────────────────────────────────────────────────

    public function test_notification_initial_state(): void
    {
        $n = $this->makeNotification();

        $this->assertEquals(1, $n->getId());
        $this->assertEquals(1, $n->getTenantId());
        $this->assertEquals(42, $n->getUserId());
        $this->assertEquals('order.created', $n->getType());
        $this->assertEquals('database', $n->getChannel()->getValue());
        $this->assertEquals('Order Created', $n->getTitle());
        $this->assertFalse($n->isRead());
        $this->assertFalse($n->isSent());
        $this->assertEquals(NotificationStatus::PENDING, $n->getStatus()->getValue());
    }

    public function test_notification_mark_as_sent(): void
    {
        $n = $this->makeNotification();
        $n->markAsSent(new \DateTime());

        $this->assertTrue($n->isSent());
        $this->assertNotNull($n->getSentAt());
        $this->assertEquals(NotificationStatus::SENT, $n->getStatus()->getValue());
    }

    public function test_notification_mark_as_failed(): void
    {
        $n = $this->makeNotification();
        $n->markAsFailed();

        $this->assertTrue($n->getStatus()->isFailed());
        $this->assertFalse($n->isRead());
    }

    public function test_notification_mark_as_read(): void
    {
        $n = $this->makeNotification(status: NotificationStatus::SENT);
        $n->markAsSent(new \DateTime());
        $n->markAsRead(new \DateTime());

        $this->assertTrue($n->isRead());
        $this->assertNotNull($n->getReadAt());
        $this->assertEquals(NotificationStatus::READ, $n->getStatus()->getValue());
    }

    public function test_notification_is_sent_also_true_when_read(): void
    {
        $n = $this->makeNotification(status: NotificationStatus::READ, readAt: '2026-01-01 12:00:00');
        // isSent() returns true for both SENT and READ statuses
        $this->assertTrue($n->isSent());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NotificationTemplate entity
    // ─────────────────────────────────────────────────────────────────────────

    public function test_template_render_replaces_placeholders(): void
    {
        $template = $this->makeTemplate();
        $rendered = $template->render([
            'order_id'      => '1234',
            'customer_name' => 'Alice',
        ]);

        $this->assertStringContainsString('1234', $rendered['subject']);
        $this->assertStringContainsString('Alice', $rendered['body']);
        $this->assertStringContainsString('1234', $rendered['body']);
    }

    public function test_template_missing_variables_detection(): void
    {
        $template = $this->makeTemplate();
        $missing  = $template->missingVariables(['order_id' => '1234']);

        $this->assertContains('customer_name', $missing);
        $this->assertNotContains('order_id', $missing);
    }

    public function test_template_no_missing_variables_when_all_provided(): void
    {
        $template = $this->makeTemplate();
        $missing  = $template->missingVariables([
            'order_id'      => '1234',
            'customer_name' => 'Bob',
        ]);

        $this->assertEmpty($missing);
    }

    public function test_template_activate_deactivate(): void
    {
        $template = $this->makeTemplate(active: false);

        $this->assertFalse($template->isActive());
        $template->activate();
        $this->assertTrue($template->isActive());
        $template->deactivate();
        $this->assertFalse($template->isActive());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NotificationPreference entity
    // ─────────────────────────────────────────────────────────────────────────

    public function test_preference_enable_disable(): void
    {
        $pref = $this->makePreference(enabled: false);

        $this->assertFalse($pref->isEnabled());
        $pref->enable();
        $this->assertTrue($pref->isEnabled());
        $pref->disable();
        $this->assertFalse($pref->isEnabled());
    }

    public function test_preference_getters(): void
    {
        $pref = $this->makePreference();

        $this->assertEquals(1, $pref->getTenantId());
        $this->assertEquals(42, $pref->getUserId());
        $this->assertEquals('order.created', $pref->getNotificationType());
        $this->assertEquals('email', $pref->getChannel());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NotificationService
    // ─────────────────────────────────────────────────────────────────────────

    public function test_service_list_for_user(): void
    {
        /** @var NotificationRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->method('findByUser')->willReturn([$this->makeNotification()]);

        $service = new NotificationService($repo);
        $result  = $service->listForUser(1, 42);

        $this->assertCount(1, $result);
    }

    public function test_service_get_by_id_found(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->method('findById')->willReturn($this->makeNotification());

        $service      = new NotificationService($repo);
        $notification = $service->getById(1);

        $this->assertEquals(1, $notification->getId());
    }

    public function test_service_get_by_id_throws_when_not_found(): void
    {
        $this->expectException(NotificationNotFoundException::class);

        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        (new NotificationService($repo))->getById(999);
    }

    public function test_service_mark_as_read_updates_notification(): void
    {
        $notification = $this->makeNotification(status: NotificationStatus::SENT);

        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->method('findById')->willReturn($notification);
        $repo->method('save')->willReturnArgument(0);

        $service = new NotificationService($repo);
        $updated = $service->markAsRead(1);

        $this->assertTrue($updated->isRead());
    }

    public function test_service_mark_as_read_skips_already_read(): void
    {
        $notification = $this->makeNotification(
            status: NotificationStatus::READ,
            readAt: '2026-01-01 12:00:00',
        );

        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->method('findById')->willReturn($notification);
        // save() must NOT be called if already read
        $repo->expects($this->never())->method('save');

        $service = new NotificationService($repo);
        $service->markAsRead(1);
    }

    public function test_service_mark_all_read(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->expects($this->once())->method('markAllReadForUser');

        (new NotificationService($repo))->markAllRead(1, 42);
    }

    public function test_service_count_unread(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->method('countUnreadForUser')->willReturn(5);

        $count = (new NotificationService($repo))->countUnread(1, 42);
        $this->assertEquals(5, $count);
    }

    public function test_service_delete_calls_repository(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->method('findById')->willReturn($this->makeNotification());
        $repo->expects($this->once())->method('delete')->with(1);

        (new NotificationService($repo))->delete(1);
    }

    public function test_service_delete_throws_when_not_found(): void
    {
        $this->expectException(NotificationNotFoundException::class);

        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        (new NotificationService($repo))->delete(999);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NotificationTemplateService
    // ─────────────────────────────────────────────────────────────────────────

    public function test_template_service_list_by_tenant(): void
    {
        $repo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repo->method('findByTenant')->willReturn([$this->makeTemplate()]);

        $result = (new NotificationTemplateService($repo))->listByTenant(1);
        $this->assertCount(1, $result);
    }

    public function test_template_service_get_by_id_found(): void
    {
        $repo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repo->method('findById')->willReturn($this->makeTemplate());

        $template = (new NotificationTemplateService($repo))->getById(1);
        $this->assertEquals('Order Created', $template->getName());
    }

    public function test_template_service_get_by_id_throws_when_not_found(): void
    {
        $this->expectException(NotificationTemplateNotFoundException::class);

        $repo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        (new NotificationTemplateService($repo))->getById(999);
    }

    public function test_template_service_create(): void
    {
        $saved = $this->makeTemplate(id: 10);

        $repo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repo->method('save')->willReturn($saved);

        $template = (new NotificationTemplateService($repo))->create([
            'tenant_id' => 1,
            'type'      => 'order.created',
            'name'      => 'Order Created',
            'channel'   => 'email',
            'subject'   => 'Subject',
            'body'      => 'Body',
            'variables' => ['order_id'],
        ]);

        $this->assertEquals(10, $template->getId());
    }

    public function test_template_service_activate(): void
    {
        $template = $this->makeTemplate(active: false);

        $repo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repo->method('findById')->willReturn($template);
        $repo->method('save')->willReturnArgument(0);

        $result = (new NotificationTemplateService($repo))->activate(1);
        $this->assertTrue($result->isActive());
    }

    public function test_template_service_deactivate(): void
    {
        $template = $this->makeTemplate(active: true);

        $repo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repo->method('findById')->willReturn($template);
        $repo->method('save')->willReturnArgument(0);

        $result = (new NotificationTemplateService($repo))->deactivate(1);
        $this->assertFalse($result->isActive());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NotificationPreferenceService
    // ─────────────────────────────────────────────────────────────────────────

    public function test_preference_service_is_enabled_when_no_record(): void
    {
        $repo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $repo->method('findByUserAndType')->willReturn(null);

        $enabled = (new NotificationPreferenceService($repo))->isEnabled(1, 42, 'order.created', 'email');
        $this->assertTrue($enabled); // default is enabled
    }

    public function test_preference_service_is_disabled_when_opted_out(): void
    {
        $pref = $this->makePreference(enabled: false);
        $repo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $repo->method('findByUserAndType')->willReturn($pref);

        $enabled = (new NotificationPreferenceService($repo))->isEnabled(1, 42, 'order.created', 'email');
        $this->assertFalse($enabled);
    }

    public function test_preference_service_set_preference_creates_new(): void
    {
        $pref = $this->makePreference(enabled: true);
        $repo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $repo->method('findByUserAndType')->willReturn(null);
        $repo->method('save')->willReturn($pref);

        $result = (new NotificationPreferenceService($repo))->setPreference(1, 42, 'order.created', 'email', true);
        $this->assertTrue($result->isEnabled());
    }

    public function test_preference_service_set_preference_updates_existing(): void
    {
        $pref = $this->makePreference(enabled: true);
        $repo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $repo->method('findByUserAndType')->willReturn($pref);
        $repo->method('save')->willReturnArgument(0);

        $result = (new NotificationPreferenceService($repo))->setPreference(1, 42, 'order.created', 'email', false);
        $this->assertFalse($result->isEnabled());
    }

    public function test_preference_service_invalid_channel_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $repo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        (new NotificationPreferenceService($repo))->setPreference(1, 42, 'order.created', 'carrier_pigeon', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NotificationChannelDispatcher
    // ─────────────────────────────────────────────────────────────────────────

    public function test_dispatcher_routes_to_correct_driver(): void
    {
        $notification = $this->makeNotification();

        $driver = $this->createMock(NotificationChannelInterface::class);
        $driver->expects($this->once())->method('send')->with($notification);

        $dispatcher = new NotificationChannelDispatcher();
        $dispatcher->addDriver('database', $driver);
        $dispatcher->dispatch($notification);
    }

    public function test_dispatcher_throws_when_no_driver_registered(): void
    {
        $this->expectException(\RuntimeException::class);

        $dispatcher = new NotificationChannelDispatcher();
        $dispatcher->dispatch($this->makeNotification());
    }

    public function test_dispatcher_has_driver(): void
    {
        $dispatcher = new NotificationChannelDispatcher();
        $dispatcher->addDriver('email', new EmailChannelDriver());

        $this->assertTrue($dispatcher->hasDriver('email'));
        $this->assertFalse($dispatcher->hasDriver('sms'));
    }

    public function test_database_channel_driver_sends_without_error(): void
    {
        $driver = new DatabaseChannelDriver();
        $driver->send($this->makeNotification()); // should not throw
        $this->assertTrue(true);
    }

    public function test_email_channel_driver_sends_without_error(): void
    {
        $driver = new EmailChannelDriver();
        $driver->send($this->makeNotification());
        $this->assertTrue(true);
    }

    public function test_sms_channel_driver_sends_without_error(): void
    {
        $driver = new SmsChannelDriver();
        $driver->send($this->makeNotification());
        $this->assertTrue(true);
    }

    public function test_push_channel_driver_sends_without_error(): void
    {
        $driver = new PushChannelDriver();
        $driver->send($this->makeNotification());
        $this->assertTrue(true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SendNotificationService
    // ─────────────────────────────────────────────────────────────────────────

    private function makeSendService(
        ?NotificationRepositoryInterface         $notifRepo     = null,
        ?NotificationTemplateRepositoryInterface $templateRepo  = null,
        ?NotificationPreferenceRepositoryInterface $prefRepo    = null,
        ?NotificationChannelDispatcher           $dispatcher    = null,
    ): SendNotificationService {
        $notifRepo    ??= $this->createMock(NotificationRepositoryInterface::class);
        $templateRepo ??= $this->createMock(NotificationTemplateRepositoryInterface::class);
        $prefRepo     ??= $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $dispatcher   ??= new NotificationChannelDispatcher();

        return new SendNotificationService($notifRepo, $templateRepo, $prefRepo, $dispatcher);
    }

    public function test_send_notification_marks_as_sent_on_success(): void
    {
        $saved = $this->makeNotification(status: NotificationStatus::PENDING);
        $sent  = $this->makeNotification(status: NotificationStatus::SENT);

        $notifRepo = $this->createMock(NotificationRepositoryInterface::class);
        $notifRepo->method('save')
            ->willReturnOnConsecutiveCalls($saved, $sent);

        $templateRepo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $templateRepo->method('findByTypeAndChannel')->willReturn(null);

        $prefRepo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $prefRepo->method('findByUserAndType')->willReturn(null);

        $driver = new DatabaseChannelDriver();
        $dispatcher = new NotificationChannelDispatcher();
        $dispatcher->addDriver('database', $driver);

        $service = new SendNotificationService($notifRepo, $templateRepo, $prefRepo, $dispatcher);
        $result  = $service->send(1, 42, 'order.created', 'database', 'Title', 'Body');

        $this->assertEquals(NotificationStatus::SENT, $result->getStatus()->getValue());
    }

    public function test_send_notification_marks_as_failed_on_channel_error(): void
    {
        $saved  = $this->makeNotification(status: NotificationStatus::PENDING);
        $failed = $this->makeNotification(status: NotificationStatus::FAILED);

        $notifRepo = $this->createMock(NotificationRepositoryInterface::class);
        $notifRepo->method('save')
            ->willReturnOnConsecutiveCalls($saved, $failed);

        $templateRepo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $templateRepo->method('findByTypeAndChannel')->willReturn(null);

        $prefRepo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $prefRepo->method('findByUserAndType')->willReturn(null);

        // Driver that throws
        $failingDriver = $this->createMock(NotificationChannelInterface::class);
        $failingDriver->method('send')->willThrowException(new \RuntimeException('SMTP error'));

        $dispatcher = new NotificationChannelDispatcher();
        $dispatcher->addDriver('database', $failingDriver);

        $service = new SendNotificationService($notifRepo, $templateRepo, $prefRepo, $dispatcher);
        $result  = $service->send(1, 42, 'order.created', 'database', 'Title', 'Body');

        $this->assertEquals(NotificationStatus::FAILED, $result->getStatus()->getValue());
    }

    public function test_send_notification_uses_template_when_available(): void
    {
        $template = $this->makeTemplate(); // channel=email

        $saved = new Notification(
            1, 1, 42, 'order.created',
            NotificationChannel::fromString('email'),
            'Your order 1234 is confirmed',   // rendered subject
            'Hi Alice, your order 1234 has been placed.',
            null,
            NotificationStatus::pending(),
            null, null, new \DateTime(), new \DateTime(),
        );
        $sent = new Notification(
            1, 1, 42, 'order.created',
            NotificationChannel::fromString('email'),
            'Your order 1234 is confirmed',
            'Hi Alice, your order 1234 has been placed.',
            null,
            NotificationStatus::sent(),
            null, new \DateTime(), new \DateTime(), new \DateTime(),
        );

        $notifRepo = $this->createMock(NotificationRepositoryInterface::class);
        $notifRepo->method('save')->willReturnOnConsecutiveCalls($saved, $sent);

        $templateRepo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $templateRepo->method('findByTypeAndChannel')->willReturn($template);

        $prefRepo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $prefRepo->method('findByUserAndType')->willReturn(null);

        $emailDriver = new EmailChannelDriver();
        $dispatcher  = new NotificationChannelDispatcher();
        $dispatcher->addDriver('email', $emailDriver);

        $service = new SendNotificationService($notifRepo, $templateRepo, $prefRepo, $dispatcher);
        $result  = $service->send(
            1, 42, 'order.created', 'email',
            'Fallback Subject', 'Fallback Body',
            ['order_id' => '1234', 'customer_name' => 'Alice'],
        );

        $this->assertEquals(NotificationStatus::SENT, $result->getStatus()->getValue());
        $this->assertStringContainsString('1234', $result->getTitle());
    }

    public function test_send_notification_skips_dispatch_for_opted_out_user(): void
    {
        $pref  = $this->makePreference(enabled: false);
        $saved = $this->makeNotification(status: NotificationStatus::PENDING);

        $notifRepo = $this->createMock(NotificationRepositoryInterface::class);
        // save() called only once (persist), NOT a second time for status update
        $notifRepo->expects($this->once())->method('save')->willReturn($saved);

        $templateRepo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $templateRepo->method('findByTypeAndChannel')->willReturn(null);

        $prefRepo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $prefRepo->method('findByUserAndType')->willReturn($pref);

        $driver = $this->createMock(NotificationChannelInterface::class);
        $driver->expects($this->never())->method('send'); // must NOT be called

        $dispatcher = new NotificationChannelDispatcher();
        $dispatcher->addDriver('database', $driver);

        $service = new SendNotificationService($notifRepo, $templateRepo, $prefRepo, $dispatcher);
        $service->send(1, 42, 'order.created', 'database', 'Title', 'Body');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Exception classes
    // ─────────────────────────────────────────────────────────────────────────

    public function test_notification_not_found_exception(): void
    {
        $e = new NotificationNotFoundException(99);
        $this->assertStringContainsString('99', $e->getMessage());
        $this->assertEquals(404, $e->getCode());
    }

    public function test_notification_template_not_found_exception(): void
    {
        $e = new NotificationTemplateNotFoundException(7);
        $this->assertStringContainsString('7', $e->getMessage());
        $this->assertEquals(404, $e->getCode());
    }

    public function test_invalid_notification_exception(): void
    {
        $e = new InvalidNotificationException('Bad data');
        $this->assertEquals('Bad data', $e->getMessage());
        $this->assertEquals(422, $e->getCode());
    }
}
