<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Core\Exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;

final class OpaqueTokenCodecTest extends TestCase
{
    private const APPLICATION_KEY = '0123456789abcdef0123456789abcdef';

    public function test_issue_and_parse_keep_plaintext_secret_out_of_the_digest(): void
    {
        $codec = new OpaqueTokenCodec(self::APPLICATION_KEY);
        $issued = $codec->issue('tat_');
        $parsed = $codec->parse($issued['plain'], 'tat_');

        self::assertNotNull($parsed);
        self::assertSame($issued['key'], $parsed['key']);
        self::assertSame($issued['digest'], $parsed['digest']);
        self::assertStringNotContainsString($issued['digest'], $issued['plain']);
    }

    public function test_parse_rejects_the_wrong_realm_prefix_and_malformed_tokens(): void
    {
        $codec = new OpaqueTokenCodec(self::APPLICATION_KEY);
        $issued = $codec->issue('tat_');

        self::assertNull($codec->parse($issued['plain'], 'pat_'));
        self::assertNull($codec->parse('not-a-token', 'tat_'));
        self::assertNull($codec->parse('tat_key.', 'tat_'));
    }

    public function test_arbitrary_digests_are_purpose_separated(): void
    {
        $codec = new OpaqueTokenCodec(self::APPLICATION_KEY);

        self::assertNotSame(
            $codec->digestArbitrary('same-value', 'invitation'),
            $codec->digestArbitrary('same-value', 'operator-invitation'),
        );
    }

    public function test_short_application_keys_are_rejected(): void
    {
        $this->expectException(ConfigurationException::class);

        new OpaqueTokenCodec('too-short');
    }
}
