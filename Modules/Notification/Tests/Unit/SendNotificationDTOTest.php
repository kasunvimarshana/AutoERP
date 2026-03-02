<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit;

use Modules\Notification\Application\DTOs\SendNotificationDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SendNotificationDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class SendNotificationDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'template_id' => 3,
            'channel'     => 'email',
            'recipient'   => 'user@example.com',
            'variables'   => ['name' => 'Alice', 'order_number' => 'SO-001'],
            'metadata'    => ['source' => 'automated'],
        ]);

        $this->assertSame(3, $dto->templateId);
        $this->assertSame('email', $dto->channel);
        $this->assertSame('user@example.com', $dto->recipient);
        $this->assertSame(['name' => 'Alice', 'order_number' => 'SO-001'], $dto->variables);
        $this->assertSame(['source' => 'automated'], $dto->metadata);
    }

    public function test_from_array_template_id_defaults_to_null(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'sms',
            'recipient' => '+1-555-0100',
        ]);

        $this->assertNull($dto->templateId);
    }

    public function test_from_array_variables_defaults_to_empty_array(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'email',
            'recipient' => 'user@example.com',
        ]);

        $this->assertSame([], $dto->variables);
    }

    public function test_from_array_metadata_defaults_to_empty_array(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'email',
            'recipient' => 'user@example.com',
        ]);

        $this->assertSame([], $dto->metadata);
    }

    public function test_template_id_cast_to_int(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'template_id' => '12',
            'channel'     => 'push',
            'recipient'   => 'device-token-abc',
        ]);

        $this->assertIsInt($dto->templateId);
        $this->assertSame(12, $dto->templateId);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'template_id' => 1,
            'channel'     => 'email',
            'recipient'   => 'admin@example.com',
            'variables'   => ['key' => 'value'],
            'metadata'    => [],
        ]);

        $array = $dto->toArray();

        $this->assertArrayHasKey('template_id', $array);
        $this->assertArrayHasKey('channel', $array);
        $this->assertArrayHasKey('recipient', $array);
        $this->assertArrayHasKey('variables', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertSame(1, $array['template_id']);
        $this->assertSame('email', $array['channel']);
    }
}
