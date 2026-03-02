<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit;

use Modules\Notification\Application\DTOs\SendNotificationDTO;
use PHPUnit\Framework\TestCase;

/**
 * Edge-case unit tests for notification template variable substitution.
 *
 * These tests exercise the pure-PHP string processing logic extracted from
 * NotificationService::sendNotification() — no database or Laravel bootstrap
 * required.  They complement the baseline tests in NotificationServiceTest
 * with boundary and edge-case scenarios.
 */
class NotificationTemplateEdgeCaseTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper: apply the same substitution logic used in NotificationService
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $variables
     */
    private function applySubstitution(string $body, array $variables, string $channel): string
    {
        $needsEscaping = in_array($channel, ['email', 'in_app'], true);

        foreach ($variables as $key => $value) {
            $placeholder = '{{ '.$key.' }}';
            $safeValue   = $needsEscaping
                ? htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : (string) $value;
            $body = str_replace($placeholder, $safeValue, $body);
        }

        return $body;
    }

    // -------------------------------------------------------------------------
    // Empty variables — body returned unchanged
    // -------------------------------------------------------------------------

    public function test_empty_variables_returns_body_unchanged(): void
    {
        $body   = 'Hello {{ name }}, your order is ready.';
        $result = $this->applySubstitution($body, [], 'email');

        $this->assertSame($body, $result);
    }

    // -------------------------------------------------------------------------
    // Numeric variable values
    // -------------------------------------------------------------------------

    public function test_numeric_value_is_cast_to_string_in_substitution(): void
    {
        $body   = 'You have {{ count }} items.';
        $result = $this->applySubstitution($body, ['count' => 5], 'sms');

        $this->assertSame('You have 5 items.', $result);
    }

    public function test_decimal_numeric_value_preserved_in_substitution(): void
    {
        $body   = 'Total: {{ amount }}';
        $result = $this->applySubstitution($body, ['amount' => '99.99'], 'email');

        $this->assertSame('Total: 99.99', $result);
    }

    // -------------------------------------------------------------------------
    // Repeated placeholder — all occurrences replaced
    // -------------------------------------------------------------------------

    public function test_repeated_placeholder_replaces_all_occurrences(): void
    {
        $body   = 'Hi {{ name }}, is {{ name }} correct?';
        $result = $this->applySubstitution($body, ['name' => 'Alice'], 'push');

        $this->assertSame('Hi Alice, is Alice correct?', $result);
    }

    // -------------------------------------------------------------------------
    // XSS — single-quote escaping for email
    // -------------------------------------------------------------------------

    public function test_single_quote_escaped_for_email_channel(): void
    {
        $body   = "User: {{ name }}";
        $result = $this->applySubstitution($body, ['name' => "O'Brien"], 'email');

        $this->assertStringContainsString('&#039;', $result);
        $this->assertStringNotContainsString("O'Brien", $result);
    }

    public function test_single_quote_not_escaped_for_sms_channel(): void
    {
        $body   = "User: {{ name }}";
        $result = $this->applySubstitution($body, ['name' => "O'Brien"], 'sms');

        $this->assertSame("User: O'Brien", $result);
    }

    // -------------------------------------------------------------------------
    // Empty string variable value
    // -------------------------------------------------------------------------

    public function test_empty_string_variable_removes_placeholder(): void
    {
        $body   = 'Hello {{ name }}!';
        $result = $this->applySubstitution($body, ['name' => ''], 'email');

        $this->assertSame('Hello !', $result);
    }

    // -------------------------------------------------------------------------
    // SendNotificationDTO — toArray round-trip
    // -------------------------------------------------------------------------

    public function test_send_notification_dto_to_array_round_trip(): void
    {
        $input = [
            'template_id' => 3,
            'channel'     => 'email',
            'recipient'   => 'alice@example.com',
            'variables'   => ['name' => 'Alice', 'code' => '123'],
            'metadata'    => ['source' => 'api'],
        ];

        $dto   = SendNotificationDTO::fromArray($input);
        $array = $dto->toArray();

        $this->assertSame(3, $array['template_id']);
        $this->assertSame('email', $array['channel']);
        $this->assertSame('alice@example.com', $array['recipient']);
        $this->assertSame(['name' => 'Alice', 'code' => '123'], $array['variables']);
        $this->assertSame(['source' => 'api'], $array['metadata']);
    }

    public function test_send_notification_dto_metadata_defaults_to_empty_array(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'push',
            'recipient' => 'device-token-xyz',
        ]);

        $this->assertSame([], $dto->metadata);
    }

    public function test_send_notification_dto_variables_defaults_to_empty_array(): void
    {
        $dto = SendNotificationDTO::fromArray([
            'channel'   => 'sms',
            'recipient' => '+447911123456',
        ]);

        $this->assertSame([], $dto->variables);
    }
}
