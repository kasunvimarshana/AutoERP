<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationTokenCodec;
use PHPUnit\Framework\TestCase;

final class PlatformOperatorInvitationTokenCodecTest extends TestCase
{
    public function test_current_digest_is_stable_across_application_key_changes(): void
    {
        $first = new PlatformOperatorInvitationTokenCodec($this->applicationKey('a'));
        $second = new PlatformOperatorInvitationTokenCodec($this->applicationKey('b'));
        $token = str_repeat('A', 72);

        self::assertSame($first->digest($token), $second->digest($token));
        self::assertNotSame($first->legacyDigest($token), $second->legacyDigest($token));
        self::assertTrue($first->matchesCurrentDigest($token, $first->digest($token)));
        self::assertFalse($first->matchesCurrentDigest($token, $first->legacyDigest($token)));
    }

    public function test_lookup_accepts_current_and_legacy_digests_during_transition(): void
    {
        $codec = new PlatformOperatorInvitationTokenCodec($this->applicationKey('c'));
        $token = str_repeat('B', 72);

        self::assertSame(
            [$codec->digest($token), $codec->legacyDigest($token)],
            $codec->lookupDigests($token),
        );
    }

    public function test_issued_token_is_url_safe_and_has_expected_entropy_length(): void
    {
        $codec = new PlatformOperatorInvitationTokenCodec($this->applicationKey('d'));
        $token = $codec->issue();

        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{72}$/', $token);
    }

    private function applicationKey(string $byte): string
    {
        return 'base64:'.base64_encode(str_repeat($byte, 32));
    }
}
