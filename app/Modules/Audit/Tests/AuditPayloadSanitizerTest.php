<?php

declare(strict_types=1);

namespace Modules\Audit\Tests;

use InvalidArgumentException;
use Modules\Audit\Services\AuditPayloadSanitizer;
use Tests\TestCase;

final class AuditPayloadSanitizerTest extends TestCase
{
    public function test_it_redacts_sensitive_keys_at_any_depth_and_deduplicates_tags(): void
    {
        config()->set('audit.payload.sensitive_keys', ['password', 'token', 'client_secret', 'card_number']);

        $sanitizer = new AuditPayloadSanitizer();
        $payload = $sanitizer->sanitize([
            'email' => 'user@example.test',
            'credentials' => [
                'password' => 'secret',
                'access_token' => 'token-value',
                'clientSecret' => 'client-secret-value',
            ],
            'payment' => ['card_number' => '4111111111111111'],
        ]);

        self::assertSame('user@example.test', $payload['email']);
        self::assertSame('[REDACTED]', $payload['credentials']['password']);
        self::assertSame('[REDACTED]', $payload['credentials']['access_token']);
        self::assertSame('[REDACTED]', $payload['credentials']['clientSecret']);
        self::assertSame('[REDACTED]', $payload['payment']['card_number']);
        self::assertSame(['financial', 'posted'], $sanitizer->sanitizeTags([' financial ', 'posted', 'financial', '']));
    }

    public function test_it_rejects_payloads_that_exceed_the_configured_size(): void
    {
        config()->set('audit.payload.max_json_bytes', 40);

        $sanitizer = new AuditPayloadSanitizer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit payload exceeds');

        $sanitizer->assertPayloadSize(['value' => str_repeat('x', 100)], null, []);
    }

    public function test_it_rejects_unsupported_payload_objects(): void
    {
        $sanitizer = new AuditPayloadSanitizer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit payloads may contain only');

        $sanitizer->sanitize(['object' => new \stdClass()]);
    }

    public function test_it_rejects_non_string_tags(): void
    {
        $sanitizer = new AuditPayloadSanitizer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tags must be strings');

        /** @phpstan-ignore-next-line */
        $sanitizer->sanitizeTags(['valid', 123]);
    }

}
