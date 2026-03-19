<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class PublicKeyService
{
    /**
     * Return the absolute path to Passport's public key.
     */
    public function publicKeyPath(): string
    {
        $configured = (string) config('passport.public_key');
        if ($configured !== '' && file_exists($configured)) {
            return $configured;
        }

        return storage_path('oauth-public.key');
    }

    /**
     * Return the absolute path to Passport's private key.
     */
    public function privateKeyPath(): string
    {
        $configured = (string) config('passport.private_key');
        if ($configured !== '' && file_exists($configured)) {
            return $configured;
        }

        return storage_path('oauth-private.key');
    }

    /**
     * Return the PEM-encoded RS256 public key contents (cached).
     */
    public function getPublicKey(): string
    {
        $cacheTtl = (int) config('sso.public_key.cache_ttl_seconds', 3600);

        return (string) Cache::remember('sso_public_key', now()->addSeconds($cacheTtl), function (): string {
            $path = $this->publicKeyPath();

            if (!file_exists($path)) {
                throw new \RuntimeException('Passport public key not found. Run `php artisan passport:keys`.');
            }

            return (string) File::get($path);
        });
    }

    /**
     * Return the public key fingerprint (SHA-256 of the DER encoding).
     */
    public function getPublicKeyFingerprint(): string
    {
        $pem = $this->getPublicKey();

        // Strip PEM headers and decode to DER
        $der = base64_decode(
            str_replace(["\n", "\r", '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'], '', $pem)
        );

        return hash('sha256', $der);
    }
}
