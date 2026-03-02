<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Integration\Application\Services\IntegrationService;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for IntegrationService — listIntegrationLogs and service structure.
 *
 * listIntegrationLogs() uses Eloquent's query builder directly (IntegrationLog::query()),
 * which requires the Laravel bootstrap, so structural checks and collection contracts
 * are validated here.  Full functional flow is covered by feature tests.
 */
class IntegrationServiceLogsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listIntegrationLogs — method existence and signature
    // -------------------------------------------------------------------------

    public function test_integration_service_has_list_integration_logs_method(): void
    {
        $this->assertTrue(
            method_exists(IntegrationService::class, 'listIntegrationLogs'),
            'IntegrationService must expose a public listIntegrationLogs() method.'
        );
    }

    public function test_list_integration_logs_has_no_parameters(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'listIntegrationLogs');
        $params     = $reflection->getParameters();

        $this->assertCount(0, $params);
    }

    public function test_list_integration_logs_return_type_is_collection(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'listIntegrationLogs');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(Collection::class, $returnType);
    }

    // -------------------------------------------------------------------------
    // dispatchWebhook — method structure
    // -------------------------------------------------------------------------

    public function test_dispatch_webhook_method_is_public(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'dispatchWebhook');

        $this->assertTrue($reflection->isPublic());
    }

    // -------------------------------------------------------------------------
    // registerWebhook — method structure
    // -------------------------------------------------------------------------

    public function test_register_webhook_method_is_public(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'registerWebhook');

        $this->assertTrue($reflection->isPublic());
    }

    // -------------------------------------------------------------------------
    // Service instantiation — verifies repository contract injection
    // -------------------------------------------------------------------------

    public function test_integration_service_can_be_instantiated_with_repository(): void
    {
        $repo    = $this->createMock(IntegrationRepositoryContract::class);
        $service = new IntegrationService($repo);

        $this->assertInstanceOf(IntegrationService::class, $service);
    }

    public function test_integration_service_list_webhooks_returns_collection(): void
    {
        $repo = $this->createMock(IntegrationRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $service = new IntegrationService($repo);
        $result  = $service->listWebhooks();

        $this->assertInstanceOf(Collection::class, $result);
    }
}
