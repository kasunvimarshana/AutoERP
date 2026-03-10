<?php

namespace App\Services;

use RuntimeException;

class JwtService
{
    private string $secret;
    private int $ttl;
    private int $refreshTtl;

    public function __construct()
    {
        $rawSecret = config('jwt.secret');

        // APP_KEY is base64-encoded; decode it for use as HMAC secret
        if (str_starts_with($rawSecret, 'base64:')) {
            $this->secret = base64_decode(substr($rawSecret, 7));
        } else {
            $this->secret = $rawSecret;
        }

        $this->ttl = (int) config('jwt.ttl', 3600);
        $this->refreshTtl = (int) config('jwt.refresh_ttl', 604800);
    }

    public function generateToken(array $user): string
    {
        $now = time();
        $payload = [
            'sub'   => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'] ?? 'user',
            'iat'   => $now,
            'exp'   => $now + $this->ttl,
            'type'  => 'access',
        ];

        return $this->encode($payload);
    }

    public function generateRefreshToken(array $user): string
    {
        $now = time();
        $payload = [
            'sub'  => $user['id'],
            'iat'  => $now,
            'exp'  => $now + $this->refreshTtl,
            'type' => 'refresh',
        ];

        return $this->encode($payload);
    }

    public function validateToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token structure');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSig = $this->sign($headerB64 . '.' . $payloadB64);

        if (!hash_equals($expectedSig, $signatureB64)) {
            throw new RuntimeException('Invalid token signature');
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (!is_array($payload)) {
            throw new RuntimeException('Invalid token payload');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new RuntimeException('Token has expired');
        }

        return $payload;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body   = $this->base64UrlEncode(json_encode($payload));
        $sig    = $this->sign($header . '.' . $body);

        return $header . '.' . $body . '.' . $sig;
    }

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
