<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Unit;

use Modules\Integration\Application\Services\IntegrationService;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use Modules\Integration\Domain\Entities\WebhookEndpoint;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for IntegrationService write-path methods: updateWebhook and deleteWebhook.
 *
 * These tests validate method existence, signatures, and public visibility.
 * No database or Laravel bootstrap required.
 */
class IntegrationServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_integration_service_has_update_webhook_method(): void
    {
        $this->assertTrue(
            method_exists(IntegrationService::class, 'updateWebhook'),
            'IntegrationService must expose a public updateWebhook() method.'
        );
    }

    public function test_integration_service_has_delete_webhook_method(): void
    {
        $this->assertTrue(
            method_exists(IntegrationService::class, 'deleteWebhook'),
            'IntegrationService must expose a public deleteWebhook() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection
    // -------------------------------------------------------------------------

    public function test_update_webhook_accepts_id_and_data(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'updateWebhook');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    public function test_update_webhook_return_type_is_webhook_endpoint(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'updateWebhook');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(WebhookEndpoint::class, $returnType);
    }

    public function test_delete_webhook_accepts_integer_id(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'deleteWebhook');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_delete_webhook_return_type_is_void(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'deleteWebhook');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('void', $returnType);
    }

    // -------------------------------------------------------------------------
    // Public visibility checks
    // -------------------------------------------------------------------------

    public function test_update_webhook_is_public(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'updateWebhook');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_webhook_is_public(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'deleteWebhook');

        $this->assertTrue($reflection->isPublic());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_integration_service_can_be_instantiated(): void
    {
        $repo    = $this->createMock(IntegrationRepositoryContract::class);
        $service = new IntegrationService($repo);

        $this->assertInstanceOf(IntegrationService::class, $service);
    }
}
