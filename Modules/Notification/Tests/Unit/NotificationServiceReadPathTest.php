<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Notification\Application\Services\NotificationService;
use Modules\Notification\Domain\Contracts\NotificationRepositoryContract;
use Modules\Notification\Domain\Entities\NotificationTemplate;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NotificationService read-path and delete-path methods.
 *
 * Validates showTemplate() and deleteTemplate() delegation and signatures.
 * No database or Laravel bootstrap required.
 */
class NotificationServiceReadPathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_notification_service_has_show_template_method(): void
    {
        $this->assertTrue(
            method_exists(NotificationService::class, 'showTemplate'),
            'NotificationService must expose a public showTemplate() method.'
        );
    }

    public function test_notification_service_has_delete_template_method(): void
    {
        $this->assertTrue(
            method_exists(NotificationService::class, 'deleteTemplate'),
            'NotificationService must expose a public deleteTemplate() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection
    // -------------------------------------------------------------------------

    public function test_show_template_accepts_integer_id(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'showTemplate');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_show_template_return_type_is_notification_template(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'showTemplate');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(NotificationTemplate::class, $returnType);
    }

    public function test_delete_template_return_type_is_void(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'deleteTemplate');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('void', $returnType);
    }

    // -------------------------------------------------------------------------
    // showTemplate — delegation to repository
    // -------------------------------------------------------------------------

    public function test_show_template_delegates_to_repository_find_or_fail(): void
    {
        $template = $this->createMock(NotificationTemplate::class);

        $repo = $this->createMock(NotificationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(7)
            ->willReturn($template);

        $service = new NotificationService($repo);
        $result  = $service->showTemplate(7);

        $this->assertSame($template, $result);
    }

    public function test_show_template_throws_when_not_found(): void
    {
        $repo = $this->createMock(NotificationRepositoryContract::class);
        $repo->method('findOrFail')
            ->willThrowException(new ModelNotFoundException());

        $service = new NotificationService($repo);

        $this->expectException(ModelNotFoundException::class);
        $service->showTemplate(999);
    }

    // -------------------------------------------------------------------------
    // showTemplate and deleteTemplate are public
    // -------------------------------------------------------------------------

    public function test_show_template_is_public(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'showTemplate');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_template_is_public(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'deleteTemplate');

        $this->assertTrue($reflection->isPublic());
    }
}
