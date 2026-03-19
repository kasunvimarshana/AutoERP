<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\ServiceToken;
use App\Exceptions\AuthException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

final class ServiceAuthService
{
    public function __construct(
        private readonly PublicKeyService $publicKeyService,
    ) {}

    /**
     * Register a new service client and return plain-text secret once.
     *
     * @param array<string, mixed> $options
     * @return array{service_token: ServiceToken, plain_secret: string}
     */
    public function registerService(string $serviceName, array $options = []): array
    {
        $clientId     = (string) Str::uuid();
        $plainSecret  = Str::random(64);
        $hashedSecret = Hash::make($plainSecret);

        $serviceToken = ServiceToken::create([
            'service_name'   => $serviceName,
            'client_id'      => $clientId,
            'client_secret'  => $hashedSecret,
            'allowed_scopes' => $options['allowed_scopes'] ?? [],
            'allowed_ips'    => $options['allowed_ips'] ?? null,
            'is_active'      => true,
            'expires_at'     => $options['expires_at'] ?? null,
        ]);

        return [
            'service_token' => $serviceToken,
            'plain_secret'  => $plainSecret,
        ];
    }

    /**
     * Issue a short-lived JWT for authenticated service-to-service communication.
     */
    public function issueServiceJwt(string $clientId, string $plainSecret): string
    {
        /** @var ServiceToken|null $serviceToken */
        $serviceToken = ServiceToken::where('client_id', $clientId)
            ->where('is_active', true)
            ->first();

        if ($serviceToken === null || !Hash::check($plainSecret, $serviceToken->client_secret)) {
            throw AuthException::invalidCredentials();
        }

        if ($serviceToken->isExpired()) {
            throw new \RuntimeException('Service client has expired.', 401);
        }

        $serviceToken->update(['last_used_at' => now()]);

        $ttlMinutes = (int) config('sso.token.service_token_ttl_minutes', 60);

        $privateKeyPath = $this->publicKeyService->privateKeyPath();

        $jwtConfig = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($privateKeyPath),
            InMemory::file($this->publicKeyService->publicKeyPath()),
        );

        $now   = new \DateTimeImmutable();
        $token = $jwtConfig->builder()
            ->issuedBy((string) config('sso.service_auth.issuer', config('app.url')))
            ->permittedFor((string) config('sso.service_auth.audience', 'kv-sso-microservices'))
            ->identifiedBy((string) Str::uuid())
            ->issuedAt($now)
            ->expiresAt($now->modify("+{$ttlMinutes} minutes"))
            ->withClaim('sub', $clientId)
            ->withClaim('service', $serviceToken->service_name)
            ->withClaim('scopes', $serviceToken->allowed_scopes ?? [])
            ->withClaim('token_type', 'service')
            ->getToken($jwtConfig->signer(), $jwtConfig->signingKey());

        return $token->toString();
    }

    /**
     * Verify a service JWT token and return the decoded payload.
     *
     * @return array<string, mixed>
     */
    public function verifyServiceJwt(string $token): array
    {
        $publicKeyPath = $this->publicKeyService->publicKeyPath();

        $jwtConfig = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($this->publicKeyService->privateKeyPath()),
            InMemory::file($publicKeyPath),
        );

        $jwtConfig->setValidationConstraints(
            new IssuedBy((string) config('sso.service_auth.issuer', config('app.url'))),
            new PermittedFor((string) config('sso.service_auth.audience', 'kv-sso-microservices')),
            new SignedWith($jwtConfig->signer(), $jwtConfig->verificationKey()),
        );

        try {
            /** @var Plain $parsed */
            $parsed = $jwtConfig->parser()->parse($token);

            $constraints = $jwtConfig->validationConstraints();
            $jwtConfig->validator()->assert($parsed, ...$constraints);

            $claims = $parsed->claims();

            return [
                'sub'        => $claims->get('sub'),
                'service'    => $claims->get('service'),
                'scopes'     => $claims->get('scopes', []),
                'token_type' => $claims->get('token_type'),
                'exp'        => $claims->get('exp'),
            ];
        } catch (\Throwable $e) {
            throw AuthException::tokenRevoked();
        }
    }
}
