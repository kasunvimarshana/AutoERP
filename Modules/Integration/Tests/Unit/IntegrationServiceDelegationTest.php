<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Integration\Application\Services\IntegrationService;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use Modules\Integration\Domain\Entities\WebhookEndpoint;
use PHPUnit\Framework\TestCase;

/**
 * Delegation tests for IntegrationService showWebhook and listDeliveries.
 *
 * These tests verify that the service correctly delegates to the injected
 * repository contract — no database or Laravel bootstrap required.
 */
class IntegrationServiceDelegationTest extends TestCase
{
    private function makeService(?IntegrationRepositoryContract $repo = null): IntegrationService
    {
        return new IntegrationService(
            $repo ?? $this->createMock(IntegrationRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // showWebhook — delegates to repository findOrFail
    // -------------------------------------------------------------------------

    public function test_show_webhook_delegates_to_repository_find_or_fail(): void
    {
        $expected = $this->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->createMock(IntegrationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(42)
            ->willReturn($expected);

        $result = $this->makeService($repo)->showWebhook(42);

        $this->assertSame($expected, $result);
    }

    public function test_show_webhook_return_type_annotation_matches_model(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'showWebhook');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(\Illuminate\Database\Eloquent\Model::class, $returnType);
    }

    public function test_show_webhook_is_not_static(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'showWebhook');
        $this->assertFalse($reflection->isStatic());
    }

    // -------------------------------------------------------------------------
    // listDeliveries — delegates to repository allDeliveries
    // -------------------------------------------------------------------------

    public function test_list_deliveries_delegates_to_repository_all_deliveries(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(IntegrationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('allDeliveries')
            ->willReturn($expected);

        $result = $this->makeService($repo)->listDeliveries();

        $this->assertSame($expected, $result);
    }

    public function test_list_deliveries_returns_collection(): void
    {
        $items = new Collection(['a', 'b', 'c']);

        $repo = $this->createMock(IntegrationRepositoryContract::class);
        $repo->method('allDeliveries')->willReturn($items);

        $result = $this->makeService($repo)->listDeliveries();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
    }

    public function test_list_deliveries_returns_empty_collection_when_no_deliveries(): void
    {
        $repo = $this->createMock(IntegrationRepositoryContract::class);
        $repo->method('allDeliveries')->willReturn(new Collection());

        $result = $this->makeService($repo)->listDeliveries();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_integration_service_can_be_instantiated_with_repository_contract(): void
    {
        $repo    = $this->createMock(IntegrationRepositoryContract::class);
        $service = new IntegrationService($repo);

        $this->assertInstanceOf(IntegrationService::class, $service);
    }

    // -------------------------------------------------------------------------
    // Regression guard — existing methods still present
    // -------------------------------------------------------------------------

    public function test_list_webhooks_method_still_present(): void
    {
        $this->assertTrue(method_exists(IntegrationService::class, 'listWebhooks'));
    }

    public function test_register_webhook_method_still_present(): void
    {
        $this->assertTrue(method_exists(IntegrationService::class, 'registerWebhook'));
    }

    public function test_dispatch_webhook_method_still_present(): void
    {
        $this->assertTrue(method_exists(IntegrationService::class, 'dispatchWebhook'));
    }

    public function test_update_webhook_method_still_present(): void
    {
        $this->assertTrue(method_exists(IntegrationService::class, 'updateWebhook'));
    }

    public function test_delete_webhook_method_still_present(): void
    {
        $this->assertTrue(method_exists(IntegrationService::class, 'deleteWebhook'));
    }
}
