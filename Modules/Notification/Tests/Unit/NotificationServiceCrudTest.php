<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit;

use Modules\Notification\Application\Services\NotificationService;
use Modules\Notification\Domain\Contracts\NotificationRepositoryContract;
use Modules\Notification\Infrastructure\Repositories\NotificationRepository;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for NotificationService updateTemplate and listLogs methods,
 * and for paginateLogs on the repository contract/implementation.
 */
class NotificationServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // updateTemplate — method existence and signature
    // -------------------------------------------------------------------------

    public function test_notification_service_has_update_template_method(): void
    {
        $this->assertTrue(
            method_exists(NotificationService::class, 'updateTemplate'),
            'NotificationService must expose a public updateTemplate() method.'
        );
    }

    public function test_update_template_is_public(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'updateTemplate');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_template_accepts_id_and_data(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'updateTemplate');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    // -------------------------------------------------------------------------
    // listLogs — method existence, signature, and default parameter
    // -------------------------------------------------------------------------

    public function test_notification_service_has_list_logs_method(): void
    {
        $this->assertTrue(
            method_exists(NotificationService::class, 'listLogs'),
            'NotificationService must expose a public listLogs() method.'
        );
    }

    public function test_list_logs_is_public(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'listLogs');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_logs_has_optional_per_page_param_with_default_15(): void
    {
        $reflection = new \ReflectionMethod(NotificationService::class, 'listLogs');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('perPage', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertTrue($params[0]->isOptional());
        $this->assertSame(15, $params[0]->getDefaultValue());
    }

    // -------------------------------------------------------------------------
    // paginateLogs — present in repository contract (via implementation)
    // -------------------------------------------------------------------------

    public function test_notification_repository_contract_has_paginate_logs_method(): void
    {
        $this->assertTrue(
            method_exists(NotificationRepositoryContract::class, 'paginateLogs'),
            'NotificationRepositoryContract must declare paginateLogs().'
        );
    }

    public function test_notification_repository_implements_paginate_logs(): void
    {
        $this->assertTrue(
            method_exists(NotificationRepository::class, 'paginateLogs'),
            'NotificationRepository must implement paginateLogs().'
        );
    }

    public function test_paginate_logs_in_repository_has_optional_per_page_param(): void
    {
        $reflection = new \ReflectionMethod(NotificationRepository::class, 'paginateLogs');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('perPage', $params[0]->getName());
        $this->assertTrue($params[0]->isOptional());
        $this->assertSame(15, $params[0]->getDefaultValue());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_notification_service_can_be_instantiated(): void
    {
        $repo    = $this->createMock(NotificationRepositoryContract::class);
        $service = new NotificationService($repo);

        $this->assertInstanceOf(NotificationService::class, $service);
    }
}
