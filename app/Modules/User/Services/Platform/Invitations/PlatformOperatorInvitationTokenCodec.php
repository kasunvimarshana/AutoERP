<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform\Invitations;

use Modules\Core\Exceptions\ConfigurationException;

final readonly class PlatformOperatorInvitationTokenCodec
{
    private const CURRENT_DIGEST_CONTEXT = 'autoerp-platform-operator-invitation-v2';
    private const LEGACY_DIGEST_CONTEXT = 'autoerp-platform-operator-invitation-v1';

    private string $legacyDigestKey;

    public function __construct(string $applicationKey)
    {
        $applicationKey = trim($applicationKey);
        if (str_starts_with($applicationKey, 'base64:')) {
            $decoded = base64_decode(substr($applicationKey, 7), true);
            $applicationKey = is_string($decoded) ? $decoded : '';
        }
        if (strlen($applicationKey) < 32) {
            throw new ConfigurationException('Application key is too short for platform invitation token derivation.');
        }

        $this->legacyDigestKey = hash_hkdf(
            'sha256',
            $applicationKey,
            32,
            self::LEGACY_DIGEST_CONTEXT,
        );
    }

    public function issue(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(54)), '+/', '-_'), '=');
    }

    /**
     * Current invitation identity is independent of the application encryption key.
     * The token contains 432 bits of cryptographic entropy, so a domain-separated
     * SHA-256 digest is sufficient for indexed lookup without weakening secrecy.
     */
    public function digest(string $plainToken): string
    {
        return hash('sha256', self::CURRENT_DIGEST_CONTEXT."\0".trim($plainToken));
    }

    public function legacyDigest(string $plainToken): string
    {
        return hash_hmac('sha256', trim($plainToken), $this->legacyDigestKey);
    }

    /** @return list<string> */
    public function lookupDigests(string $plainToken): array
    {
        return array_values(array_unique([
            $this->digest($plainToken),
            $this->legacyDigest($plainToken),
        ]));
    }

    public function matchesCurrentDigest(string $plainToken, string $storedDigest): bool
    {
        $storedDigest = trim($storedDigest);

        return $storedDigest !== '' && hash_equals($storedDigest, $this->digest($plainToken));
    }
}
