<?php

namespace Tests\Unit\Notification;

use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Infrastructure\Jobs\SendNotificationJob;
use Modules\Notification\Infrastructure\Repositories\NotificationRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SendNotificationJob and the Notification repository contract.
 *
 * Verifies that the job is correctly configured for fault-tolerance
 * (timeout, retries, backoff) and that it stores the payload.
 * Also verifies the NotificationRepositoryInterface structural contract.
 */
class SendNotificationJobTest extends TestCase
{
    public function test_job_has_correct_timeout(): void
    {
        $job = $this->makeJob();

        $this->assertSame(120, $job->timeout);
    }

    public function test_job_has_correct_retry_count(): void
    {
        $job = $this->makeJob();

        $this->assertSame(3, $job->tries);
    }

    public function test_job_has_correct_backoff(): void
    {
        $job = $this->makeJob();

        $this->assertSame(60, $job->backoff);
    }

    public function test_job_stores_constructor_arguments(): void
    {
        $job = new SendNotificationJob(
            tenantId: 'tenant-1',
            userId: 'user-1',
            type: 'order.confirmed',
            channel: 'email',
            data: ['order_id' => 'ord-99'],
        );

        $this->assertSame('tenant-1', $job->tenantId);
        $this->assertSame('user-1', $job->userId);
        $this->assertSame('order.confirmed', $job->type);
        $this->assertSame('email', $job->channel);
        $this->assertSame(['order_id' => 'ord-99'], $job->data);
    }

    public function test_job_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $this->makeJob(),
        );
    }

    // -------------------------------------------------------------------------
    // NotificationRepositoryInterface — structural integrity
    // -------------------------------------------------------------------------

    public function test_notification_repository_interface_declares_required_methods(): void
    {
        $methods = get_class_methods(NotificationRepositoryInterface::class);

        $this->assertContains('paginateForUser', $methods);
        $this->assertContains('findById', $methods);
        $this->assertContains('findByIdAndUser', $methods);
        $this->assertContains('markRead', $methods);
        $this->assertContains('markAllReadForUser', $methods);
        $this->assertContains('countUnreadForUser', $methods);
        $this->assertContains('delete', $methods);
    }

    public function test_notification_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_a(NotificationRepository::class, NotificationRepositoryInterface::class, true),
            'NotificationRepository must implement NotificationRepositoryInterface'
        );
    }

    // -----------------------------------------------------------------------

    private function makeJob(): SendNotificationJob
    {
        return new SendNotificationJob(
            tenantId: 'tenant-uuid',
            userId: 'user-uuid',
            type: 'test.event',
            channel: 'in_app',
            data: [],
        );
    }
}
