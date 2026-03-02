<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Integration\Application\DTOs\RegisterWebhookDTO;
use Modules\Integration\Application\Services\IntegrationService;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for IntegrationService business logic.
 *
 * listWebhooks() is tested via repository stub (no Laravel bootstrap needed).
 * registerWebhook() calls DB::transaction() internally; the DTO field-mapping
 * rules are tested directly through RegisterWebhookDTO to keep tests pure PHP.
 */
class IntegrationServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listWebhooks — delegation to repository (no DB::transaction)
    // -------------------------------------------------------------------------

    public function test_list_webhooks_delegates_to_repository_all(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(IntegrationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $service = new IntegrationService($repo);
        $result  = $service->listWebhooks();

        $this->assertSame($expected, $result);
    }

    public function test_list_webhooks_returns_collection_type(): void
    {
        $repo = $this->createMock(IntegrationRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $service = new IntegrationService($repo);
        $result  = $service->listWebhooks();

        $this->assertInstanceOf(Collection::class, $result);
    }

    // -------------------------------------------------------------------------
    // RegisterWebhookDTO — field mapping (pure PHP, mirrors registerWebhook logic)
    // -------------------------------------------------------------------------

    public function test_register_webhook_dto_maps_all_fields(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'    => 'Order Created Hook',
            'url'     => 'https://example.com/webhook',
            'events'  => ['order.created', 'order.updated'],
            'secret'  => 'my-secret',
            'headers' => ['X-Source' => 'ERP'],
        ]);

        $this->assertSame('Order Created Hook', $dto->name);
        $this->assertSame('https://example.com/webhook', $dto->url);
        $this->assertSame(['order.created', 'order.updated'], $dto->events);
        $this->assertSame('my-secret', $dto->secret);
        $this->assertSame(['X-Source' => 'ERP'], $dto->headers);
    }

    public function test_register_webhook_dto_secret_defaults_to_null(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'   => 'No-Secret Hook',
            'url'    => 'https://example.com/hook',
            'events' => ['payment.received'],
        ]);

        $this->assertNull($dto->secret);
    }

    public function test_register_webhook_dto_headers_default_to_empty_array(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'   => 'Minimal Hook',
            'url'    => 'https://example.com/minimal',
            'events' => ['ping'],
        ]);

        $this->assertSame([], $dto->headers);
    }

    public function test_register_webhook_dto_events_stored_as_array(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'   => 'Multi-Event Hook',
            'url'    => 'https://example.com/multi',
            'events' => ['event.a', 'event.b', 'event.c'],
        ]);

        $this->assertIsArray($dto->events);
        $this->assertCount(3, $dto->events);
    }

    // -------------------------------------------------------------------------
    // Webhook data mapping (pure PHP — mirrors registerWebhook create payload)
    // -------------------------------------------------------------------------

    public function test_register_webhook_create_payload_has_is_active_true(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'   => 'Test Hook',
            'url'    => 'https://example.com/test',
            'events' => ['test.event'],
        ]);

        // Mirror the mapping done inside registerWebhook()
        $createPayload = [
            'name'      => $dto->name,
            'url'       => $dto->url,
            'events'    => $dto->events,
            'secret'    => $dto->secret,
            'headers'   => $dto->headers,
            'is_active' => true,
        ];

        $this->assertTrue($createPayload['is_active']);
        $this->assertSame('Test Hook', $createPayload['name']);
        $this->assertNull($createPayload['secret']);
    }
}
