<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use Modules\Auth\Contracts\Providers\SsoProviderInterface;
use Modules\Auth\DTOs\AuthorizeClientData;
use Modules\Auth\DTOs\ExchangeAuthorizationCodeData;
use Modules\Auth\Repositories\AuthAuthorizationCodeRepositoryInterface;
use Modules\Auth\Repositories\AuthClientRepositoryInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\DTOs\DataRecord;

final class DatabaseSsoProvider implements SsoProviderInterface
{
    public function __construct(
        private readonly AuthClientRepositoryInterface $clients,
        private readonly AuthAuthorizationCodeRepositoryInterface $authorizationCodes,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function authorizeClient(AuthorizeClientData $data): ?array
    {
        $client = $this->clients->findByClientKey($data->tenantId, $data->clientKey);
        if ($client === null) {
            return null;
        }

        if ((string) $client->get('status') !== 'active') {
            return null;
        }

        $isConfidential = (bool) $client->get('is_confidential', false);
        $clientSecretHash = (string) $client->get('client_secret_hash', '');

        if (
            $isConfidential
            && (! is_string($data->clientSecret)
                || ! $this->passwordHasher->verify($data->clientSecret, $clientSecretHash))
        ) {
            return null;
        }

        $allowedRedirects = $client->get('redirect_uris', []);
        if (
            is_array($allowedRedirects)
            && $data->redirectUri !== null
            && ! in_array($data->redirectUri, $allowedRedirects, true)
        ) {
            return null;
        }

        $allowedScopes = $client->get('allowed_scopes', []);
        if (is_array($allowedScopes) && $allowedScopes !== []) {
            foreach ($data->scopes as $scope) {
                if (! in_array($scope, $allowedScopes, true)) {
                    return null;
                }
            }
        }

        $codeKey = Str::random(48);
        $codeSecret = Str::random(64);

        $authorizationCode = $this->authorizationCodes->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'provider_id' => $client->get('provider_id'),
            'client_id' => $client->id(),
            'identity_id' => $data->identityId,
            'session_id' => $data->sessionId,
            'user_id' => $data->userId,
            'code_key' => $codeKey,
            'code_hash' => $this->passwordHasher->hash($codeSecret),
            'scopes' => $data->scopes,
            'code_challenge' => $data->codeChallenge,
            'code_challenge_method' => $data->codeChallengeMethod,
            'redirect_uri' => $data->redirectUri,
            'status' => 'pending',
            'issued_at' => now(),
            'expires_at' => now()->addSeconds($data->ttlSeconds),
            'row_version' => 1,
            'metadata' => null,
        ]);

        return [
            'authorization_code' => $codeKey.'.'.$codeSecret,
            'authorization_code_id' => $authorizationCode->id(),
            'expires_at' => $authorizationCode->get('expires_at'),
            'client_id' => $client->id(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function exchangeAuthorizationCode(ExchangeAuthorizationCodeData $data): ?array
    {
        [$codeKey, $codeSecret] = $this->splitToken($data->authorizationCode);
        if ($codeKey === null || $codeSecret === null) {
            return null;
        }

        $authorizationCode = $this->authorizationCodes->findActiveByCodeKey($data->tenantId, $codeKey);
        if ($authorizationCode === null) {
            return null;
        }

        if (! $this->passwordHasher->verify($codeSecret, (string) $authorizationCode->get('code_hash', ''))) {
            return null;
        }

        $expiresAt = $authorizationCode->get('expires_at');
        if ($expiresAt !== null && now()->greaterThan($expiresAt)) {
            $this->authorizationCodes->update($authorizationCode->id(), [
                'status' => 'expired',
                'row_version' => ((int) $authorizationCode->get('row_version', 1)) + 1,
            ]);

            return null;
        }

        $client = $this->clients->findById((int) $authorizationCode->get('client_id'));
        if ($client === null || (string) $client->get('status', '') !== 'active') {
            return null;
        }

        if ((string) $client->get('client_key', '') !== $data->clientKey) {
            return null;
        }

        if (! $this->validateClientSecret($data->clientSecret, $client)) {
            return null;
        }

        if (! $this->validateGrantType($client, 'authorization_code')) {
            return null;
        }

        $storedRedirectUri = $authorizationCode->get('redirect_uri');
        if (
            $storedRedirectUri !== null
            && (string) $storedRedirectUri !== ''
            && (string) $storedRedirectUri !== (string) ($data->redirectUri ?? '')
        ) {
            return null;
        }

        if (! $this->validateCodeVerifier($authorizationCode, $data->codeVerifier)) {
            return null;
        }

        $consumed = $this->authorizationCodes->consume(
            (int) $authorizationCode->id(),
            (int) $authorizationCode->get('row_version', 1),
        );
        if (! $consumed) {
            return null;
        }

        $scopes = is_array($authorizationCode->get('scopes'))
            ? array_values($authorizationCode->get('scopes'))
            : [];

        return [
            'tenant_id' => $authorizationCode->get('tenant_id'),
            'organization_unit_id' => $authorizationCode->get('organization_unit_id'),
            'provider_id' => $authorizationCode->get('provider_id'),
            'client_id' => $authorizationCode->get('client_id'),
            'identity_id' => $authorizationCode->get('identity_id'),
            'session_id' => $authorizationCode->get('session_id'),
            'user_id' => $authorizationCode->get('user_id'),
            'scopes' => $data->scopes !== [] ? $data->scopes : $scopes,
            'authorization_code_id' => $authorizationCode->id(),
        ];
    }

    private function validateClientSecret(?string $clientSecret, DataRecord $client): bool
    {
        $isConfidential = (bool) $client->get('is_confidential', false);
        if (! $isConfidential) {
            return true;
        }

        $clientSecretHash = (string) $client->get('client_secret_hash', '');

        return is_string($clientSecret)
            && $clientSecret !== ''
            && $this->passwordHasher->verify($clientSecret, $clientSecretHash);
    }

    private function validateGrantType(DataRecord $client, string $grantType): bool
    {
        $allowedGrantTypes = $client->get('allowed_grant_types', []);
        if (! is_array($allowedGrantTypes) || $allowedGrantTypes === []) {
            return true;
        }

        return in_array($grantType, $allowedGrantTypes, true);
    }

    private function validateCodeVerifier(DataRecord $authorizationCode, ?string $codeVerifier): bool
    {
        $challenge = $authorizationCode->get('code_challenge');
        if (! is_string($challenge) || $challenge === '') {
            return true;
        }

        if (! is_string($codeVerifier) || trim($codeVerifier) === '') {
            return false;
        }

        $method = strtoupper((string) $authorizationCode->get('code_challenge_method', 'plain'));
        if ($method === 'S256') {
            return $challenge === rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        }

        return $challenge === $codeVerifier;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function splitToken(string $token): array
    {
        $parts = explode('.', trim($token), 2);
        if (count($parts) !== 2) {
            return [null, null];
        }

        if ($parts[0] === '' || $parts[1] === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }
}
