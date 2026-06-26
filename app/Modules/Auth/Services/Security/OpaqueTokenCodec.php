<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Security;

use InvalidArgumentException;

final readonly class OpaqueTokenCodec
{
    private const KEY_DERIVATION_CONTEXT = 'autoerp-auth-opaque-token-v1';

    private const MINIMUM_APPLICATION_KEY_BYTES = 32;

    private const LOOKUP_KEY_BYTES = 18;

    private const SECRET_BYTES = 32;

    private string $digestKey;

    public function __construct(string $applicationKey)
    {
        $applicationKey = $this->decodeApplicationKey(trim($applicationKey));

        if (strlen($applicationKey) < self::MINIMUM_APPLICATION_KEY_BYTES) {
            throw new InvalidArgumentException(
                'Application key is too short for Auth token derivation.',
            );
        }

        $this->digestKey = hash_hkdf(
            'sha256',
            $applicationKey,
            32,
            self::KEY_DERIVATION_CONTEXT,
        );
    }

    /** @return array{plain:string,key:string,digest:string} */
    public function issue(string $prefix): array
    {
        if (trim($prefix) === '') {
            throw new InvalidArgumentException('Opaque token prefix must not be empty.');
        }

        $key = $prefix.$this->base64Url(random_bytes(self::LOOKUP_KEY_BYTES));
        $secret = $this->base64Url(random_bytes(self::SECRET_BYTES));

        return [
            'plain' => $key.'.'.$secret,
            'key' => $key,
            'digest' => $this->digest($secret),
        ];
    }

    /** @return array{key:string,digest:string}|null */
    public function parse(string $plainToken, string $expectedPrefix): ?array
    {
        $parts = explode('.', trim($plainToken), 2);
        if (
            count($parts) !== 2
            || ! str_starts_with($parts[0], $expectedPrefix)
            || $parts[1] === ''
        ) {
            return null;
        }

        return [
            'key' => $parts[0],
            'digest' => $this->digest($parts[1]),
        ];
    }

    public function digestArbitrary(string $value, string $purpose): string
    {
        if (trim($purpose) === '') {
            throw new InvalidArgumentException('Digest purpose must not be empty.');
        }

        return hash_hmac('sha256', $purpose."\0".$value, $this->digestKey);
    }

    private function decodeApplicationKey(string $applicationKey): string
    {
        if (! str_starts_with($applicationKey, 'base64:')) {
            return $applicationKey;
        }

        $decoded = base64_decode(substr($applicationKey, 7), true);

        return is_string($decoded) ? $decoded : '';
    }

    private function digest(string $secret): string
    {
        return hash_hmac('sha256', $secret, $this->digestKey);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
