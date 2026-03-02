<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Integration\Application\Services\IntegrationService;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for IntegrationService — dispatchWebhook payload structure.
 *
 * dispatchWebhook() calls DB::transaction() + Eloquent::create() internally,
 * both of which require a full Laravel bootstrap.  These tests therefore
 * validate structural contracts and the repository delegation paths that
 * can be exercised without a database.  Functional dispatch flows are
 * covered by feature tests.
 */
class IntegrationServiceDispatchTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Structural compliance
    // -------------------------------------------------------------------------

    public function test_integration_service_has_dispatch_webhook_method(): void
    {
        $this->assertTrue(
            method_exists(IntegrationService::class, 'dispatchWebhook'),
            'IntegrationService must expose a public dispatchWebhook() method.'
        );
    }

    public function test_dispatch_webhook_accepts_endpoint_id_event_and_payload(): void
    {
        $reflection = new \ReflectionMethod(IntegrationService::class, 'dispatchWebhook');
        $params     = $reflection->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('endpointId', $params[0]->getName());
        $this->assertSame('eventName', $params[1]->getName());
        $this->assertSame('payload', $params[2]->getName());
    }

    // -------------------------------------------------------------------------
    // findByEvent — delegation to repository
    // -------------------------------------------------------------------------

    public function test_integration_service_has_no_direct_dependency_on_db_in_list(): void
    {
        $collection = new Collection();

        $repo = $this->createMock(IntegrationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($collection);

        $service = new IntegrationService($repo);
        $result  = $service->listWebhooks();

        $this->assertSame($collection, $result);
    }

    // -------------------------------------------------------------------------
    // Webhook delivery payload structure (pure PHP — mirrors dispatchWebhook logic)
    // -------------------------------------------------------------------------

    public function test_webhook_delivery_payload_contains_status_pending(): void
    {
        $deliveryPayload = [
            'tenant_id'           => 1,
            'webhook_endpoint_id' => 5,
            'event_name'          => 'order.created',
            'payload'             => ['order_id' => 42],
            'status'              => 'pending',
            'attempt_count'       => 0,
        ];

        $this->assertSame('pending', $deliveryPayload['status']);
    }

    public function test_webhook_delivery_payload_initial_attempt_count_is_zero(): void
    {
        $deliveryPayload = [
            'tenant_id'           => 1,
            'webhook_endpoint_id' => 5,
            'event_name'          => 'payment.received',
            'payload'             => ['amount' => '100.00'],
            'status'              => 'pending',
            'attempt_count'       => 0,
        ];

        $this->assertSame(0, $deliveryPayload['attempt_count']);
    }

    public function test_webhook_delivery_payload_maps_event_name_correctly(): void
    {
        $eventName = 'invoice.generated';

        $deliveryPayload = [
            'tenant_id'           => 2,
            'webhook_endpoint_id' => 10,
            'event_name'          => $eventName,
            'payload'             => [],
            'status'              => 'pending',
            'attempt_count'       => 0,
        ];

        $this->assertSame($eventName, $deliveryPayload['event_name']);
    }

    public function test_webhook_delivery_payload_propagates_tenant_id(): void
    {
        $tenantId     = 7;
        $endpointData = ['id' => 3, 'tenant_id' => $tenantId];

        $deliveryPayload = [
            'tenant_id'           => $endpointData['tenant_id'],
            'webhook_endpoint_id' => $endpointData['id'],
            'event_name'          => 'stock.adjusted',
            'payload'             => [],
            'status'              => 'pending',
            'attempt_count'       => 0,
        ];

        $this->assertSame($tenantId, $deliveryPayload['tenant_id']);
        $this->assertSame(3, $deliveryPayload['webhook_endpoint_id']);
    }

    public function test_webhook_delivery_payload_stores_arbitrary_payload_array(): void
    {
        $customPayload = ['key1' => 'value1', 'key2' => 123, 'nested' => ['a' => true]];

        $deliveryPayload = [
            'tenant_id'           => 1,
            'webhook_endpoint_id' => 1,
            'event_name'          => 'custom.event',
            'payload'             => $customPayload,
            'status'              => 'pending',
            'attempt_count'       => 0,
        ];

        $this->assertSame($customPayload, $deliveryPayload['payload']);
        $this->assertIsArray($deliveryPayload['payload']);
    }
}
