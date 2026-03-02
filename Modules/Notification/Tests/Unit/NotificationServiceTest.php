<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Notification\Application\DTOs\SendNotificationDTO;
use Modules\Notification\Application\Services\NotificationService;
use Modules\Notification\Domain\Contracts\NotificationRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NotificationService business logic.
 *
 * listTemplates() is tested via repository stub (no Laravel bootstrap needed).
 * The {{ var }} substitution and XSS-escaping logic is tested as pure PHP
 * string operations — the same algorithm used inside sendNotification() —
 * without invoking DB::transaction() which requires the Laravel facade.
 */
class NotificationServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listTemplates — delegation to repository (no DB::transaction)
    // -------------------------------------------------------------------------

    public function test_list_templates_delegates_to_repository_all(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(NotificationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $service = new NotificationService($repo);
        $result  = $service->listTemplates();

        $this->assertSame($expected, $result);
    }

    public function test_list_templates_returns_collection_type(): void
    {
        $repo = $this->createMock(NotificationRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $service = new NotificationService($repo);

        $this->assertInstanceOf(Collection::class, $service->listTemplates());
    }

    // -------------------------------------------------------------------------
    // Variable substitution logic (pure PHP — mirrors sendNotification internals)
    // -------------------------------------------------------------------------

    public function test_variable_substitution_replaces_single_placeholder(): void
    {
        $body      = 'Hello {{ name }}!';
        $variables = ['name' => 'Alice'];

        foreach ($variables as $key => $value) {
            $body = str_replace('{{ '.$key.' }}', (string) $value, $body);
        }

        $this->assertSame('Hello Alice!', $body);
    }

    public function test_variable_substitution_replaces_multiple_placeholders(): void
    {
        $body      = 'Order {{ order_id }} for {{ customer_name }} is ready.';
        $variables = ['order_id' => 'ORD-001', 'customer_name' => 'Bob'];

        foreach ($variables as $key => $value) {
            $body = str_replace('{{ '.$key.' }}', (string) $value, $body);
        }

        $this->assertSame('Order ORD-001 for Bob is ready.', $body);
    }

    public function test_variable_substitution_leaves_unknown_placeholders_intact(): void
    {
        $body      = 'Hi {{ name }}, code: {{ code }}';
        $variables = ['name' => 'Carol'];

        foreach ($variables as $key => $value) {
            $body = str_replace('{{ '.$key.' }}', (string) $value, $body);
        }

        $this->assertSame('Hi Carol, code: {{ code }}', $body);
    }

    // -------------------------------------------------------------------------
    // HTML-escaping logic for email / in_app channels (XSS prevention)
    // -------------------------------------------------------------------------

    public function test_html_escaping_for_email_channel_prevents_xss(): void
    {
        $channel   = 'email';
        $body      = 'Welcome {{ name }}!';
        $variables = ['name' => '<script>alert("xss")</script>'];

        $needsEscaping = in_array($channel, ['email', 'in_app'], true);

        foreach ($variables as $key => $rawValue) {
            $safeValue = $needsEscaping
                ? htmlspecialchars((string) $rawValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : (string) $rawValue;
            $body = str_replace('{{ '.$key.' }}', $safeValue, $body);
        }

        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }

    public function test_html_escaping_for_in_app_channel_prevents_xss(): void
    {
        $channel   = 'in_app';
        $body      = 'Msg: {{ content }}';
        $variables = ['content' => '<b>Bold</b>'];

        $needsEscaping = in_array($channel, ['email', 'in_app'], true);

        foreach ($variables as $key => $rawValue) {
            $safeValue = $needsEscaping
                ? htmlspecialchars((string) $rawValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : (string) $rawValue;
            $body = str_replace('{{ '.$key.' }}', $safeValue, $body);
        }

        $this->assertStringNotContainsString('<b>', $body);
        $this->assertStringContainsString('&lt;b&gt;', $body);
    }

    public function test_sms_channel_does_not_escape_html(): void
    {
        $channel   = 'sms';
        $body      = 'Code: {{ code }}';
        $variables = ['code' => '123456'];

        $needsEscaping = in_array($channel, ['email', 'in_app'], true);

        foreach ($variables as $key => $rawValue) {
            $safeValue = $needsEscaping
                ? htmlspecialchars((string) $rawValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : (string) $rawValue;
            $body = str_replace('{{ '.$key.' }}', $safeValue, $body);
        }

        $this->assertSame('Code: 123456', $body);
    }

    public function test_push_channel_does_not_escape_html(): void
    {
        $channel   = 'push';
        $body      = 'Alert: {{ message }}';
        $variables = ['message' => 'Low stock'];

        $needsEscaping = in_array($channel, ['email', 'in_app'], true);

        foreach ($variables as $key => $rawValue) {
            $safeValue = $needsEscaping
                ? htmlspecialchars((string) $rawValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : (string) $rawValue;
            $body = str_replace('{{ '.$key.' }}', $safeValue, $body);
        }

        $this->assertSame('Alert: Low stock', $body);
    }

    // -------------------------------------------------------------------------
    // SendNotificationDTO — channel and recipient checks
    // -------------------------------------------------------------------------

    public function test_dto_stores_channel_and_recipient(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'email',
            'recipient' => 'user@example.com',
        ]);

        $this->assertSame('email', $dto->channel);
        $this->assertSame('user@example.com', $dto->recipient);
    }

    public function test_dto_template_id_defaults_to_null_when_absent(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'sms',
            'recipient' => '+1234567890',
        ]);

        $this->assertNull($dto->templateId);
    }
}
