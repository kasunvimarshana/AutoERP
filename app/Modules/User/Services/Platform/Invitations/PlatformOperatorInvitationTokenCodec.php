<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform\Invitations;

use InvalidArgumentException;

final readonly class PlatformOperatorInvitationTokenCodec
{
    private string $digestKey;

    public function __construct(string $applicationKey)
    {
        $applicationKey = trim($applicationKey);
        if (str_starts_with($applicationKey, 'base64:')) {
            $decoded = base64_decode(substr($applicationKey, 7), true);
            $applicationKey = is_string($decoded) ? $decoded : '';
        }
        if (strlen($applicationKey) < 32) {
            throw new InvalidArgumentException('Application key is too short for platform invitation token derivation.');
        }

        $this->digestKey = hash_hkdf(
            'sha256',
            $applicationKey,
            32,
            'autoerp-platform-operator-invitation-v1',
        );
    }

    public function issue(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(54)), '+/', '-_'), '=');
    }

    public function digest(string $plainToken): string
    {
        return hash_hmac('sha256', trim($plainToken), $this->digestKey);
    }
}
