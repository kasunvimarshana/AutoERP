<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Unit;

use Modules\Integration\Application\Services\IntegrationService;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use Modules\Integration\Infrastructure\Repositories\IntegrationRepository;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for IntegrationService showWebhook and listDeliveries methods,
 * and for allDeliveries on the repository contract/implementation.
 */
class IntegrationServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // showWebhook — method existence and signature
    // -------------------------------------------------------------------------

    public function test_integration_service_has_show_webhook_method(): void
    {
        $this->assertTrue(
            method_exists(IntegrationService::class, 'showWebhook'),
            'IntegrationService must expose a public showWebhook() method.'
        );
    }

    public function test_show_webhook_is_public(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'showWebhook');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_webhook_accepts_id_param(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'showWebhook');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // listDeliveries — method existence and signature
    // -------------------------------------------------------------------------

    public function test_integration_service_has_list_deliveries_method(): void
    {
        $this->assertTrue(
            method_exists(IntegrationService::class, 'listDeliveries'),
            'IntegrationService must expose a public listDeliveries() method.'
        );
    }

    public function test_list_deliveries_is_public(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'listDeliveries');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_deliveries_has_no_required_params(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'listDeliveries');

        $this->assertCount(0, $reflection->getParameters());
    }

    // -------------------------------------------------------------------------
    // allDeliveries — present in repository contract and implementation
    // -------------------------------------------------------------------------

    public function test_integration_repository_contract_has_all_deliveries_method(): void
    {
        $this->assertTrue(
            method_exists(IntegrationRepositoryContract::class, 'allDeliveries'),
            'IntegrationRepositoryContract must declare allDeliveries().'
        );
    }

    public function test_integration_repository_implements_all_deliveries(): void
    {
        $this->assertTrue(
            method_exists(IntegrationRepository::class, 'allDeliveries'),
            'IntegrationRepository must implement allDeliveries().'
        );
    }

    public function test_all_deliveries_in_repository_has_no_required_params(): void
    {
        $reflection = new \ReflectionMethod(IntegrationRepository::class, 'allDeliveries');

        $this->assertCount(0, $reflection->getParameters());
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
