<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Unit;

use Modules\Integration\Application\DTOs\RegisterWebhookDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RegisterWebhookDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class RegisterWebhookDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'   => 'Order Created',
            'url'    => 'https://api.example.com/webhooks/orders',
            'events' => ['order.created', 'order.updated'],
        ]);

        $this->assertSame('Order Created', $dto->name);
        $this->assertSame('https://api.example.com/webhooks/orders', $dto->url);
        $this->assertSame(['order.created', 'order.updated'], $dto->events);
        $this->assertNull($dto->secret);
        $this->assertSame([], $dto->headers);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'    => 'Invoice Events',
            'url'     => 'https://billing.example.com/webhooks',
            'events'  => ['invoice.paid'],
            'secret'  => 'mysupersecret',
            'headers' => ['X-Source' => 'erp-platform'],
        ]);

        $this->assertSame('mysupersecret', $dto->secret);
        $this->assertSame(['X-Source' => 'erp-platform'], $dto->headers);
    }

    public function test_secret_defaults_to_null(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'   => 'No Secret',
            'url'    => 'https://example.com/hook',
            'events' => ['stock.adjusted'],
        ]);

        $this->assertNull($dto->secret);
    }

    public function test_headers_defaults_to_empty_array(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'   => 'No Headers',
            'url'    => 'https://example.com/hook',
            'events' => ['product.created'],
        ]);

        $this->assertSame([], $dto->headers);
    }

    public function test_events_stored_as_array(): void
    {
        $dto = RegisterWebhookDTO::fromArray([
            'name'   => 'Multi-event Hook',
            'url'    => 'https://example.com/hook',
            'events' => ['a', 'b', 'c'],
        ]);

        $this->assertIsArray($dto->events);
        $this->assertCount(3, $dto->events);
        $this->assertContains('a', $dto->events);
        $this->assertContains('b', $dto->events);
        $this->assertContains('c', $dto->events);
    }
}
