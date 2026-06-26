<?php

declare(strict_types=1);

namespace Modules\Auth\Console\Commands;

use DateTimeImmutable;
use Illuminate\Console\Command;
use Modules\Auth\Enums\ClientStatus;
use Modules\Auth\Enums\GrantType;
use Modules\Auth\Models\AuthClientModel;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Throwable;

final class AuthClientCreateCommand extends Command
{
    protected $signature = 'auth:client-create
        {tenant_id : Tenant identifier}
        {client_key : Stable client key}
        {client_name : Display name}
        {--scopes=tenant : Comma-separated registered scopes}
        {--grant-types=authorization_code,refresh_token : Comma-separated grant types}
        {--redirect-uris= : Comma-separated exact redirect URIs}
        {--public : Create a public PKCE client without a client secret}
        {--first-party : Mark the client as first-party}
        {--expires-at= : Optional future ISO-8601 expiry}';

    protected $description = 'Create a tenant-owned OAuth client with exact redirects and server-owned security policy.';

    public function __construct(
        private readonly PasswordHasherInterface $passwords,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly ClockInterface $clock,
        private readonly AuthSecurityConfig $config,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = filter_var($this->argument('tenant_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($tenantId === false) {
            return $this->fail('Tenant identifier must be a positive integer.');
        }

        $clientKey = trim((string) $this->argument('client_key'));
        $clientName = trim((string) $this->argument('client_name'));
        if ($clientKey === '' || $clientName === '') {
            return $this->fail('Client key and client name are required.');
        }

        $scopes = $this->csv((string) $this->option('scopes'));
        if ($scopes === [] || array_diff($scopes, $this->config->oauthScopes) !== []) {
            return $this->fail('Scopes must be a non-empty subset of the registered Auth scope catalogue.');
        }

        $supportedGrants = [GrantType::AUTHORIZATION_CODE->value, GrantType::REFRESH_TOKEN->value];
        $grants = $this->csv((string) $this->option('grant-types'));
        if ($grants === [] || array_diff($grants, $supportedGrants) !== []) {
            return $this->fail('Grant types must be authorization_code and/or refresh_token.');
        }

        $redirectUris = $this->csv((string) $this->option('redirect-uris'));
        if (in_array(GrantType::AUTHORIZATION_CODE->value, $grants, true) && $redirectUris === []) {
            return $this->fail('Authorization-code clients require at least one exact redirect URI.');
        }
        foreach ($redirectUris as $redirectUri) {
            if (filter_var($redirectUri, FILTER_VALIDATE_URL) === false) {
                return $this->fail('Every redirect URI must be an absolute URL.');
            }
        }

        $expiresAt = $this->parseExpiry($this->option('expires-at'));
        if ($this->option('expires-at') !== null && $expiresAt === null) {
            return $this->fail('expires-at must be a future ISO-8601 timestamp.');
        }

        $isConfidential = ! (bool) $this->option('public');
        $plainSecret = $this->confidentialSecret($isConfidential);
        if ($isConfidential && $plainSecret === null) {
            return self::FAILURE;
        }

        try {
            $client = $this->executionContext->runForTenant((int) $tenantId, function () use (
                $tenantId,
                $clientKey,
                $clientName,
                $plainSecret,
                $isConfidential,
                $scopes,
                $grants,
                $redirectUris,
                $expiresAt,
            ): AuthClientModel {
                return AuthClientModel::query()->create([
                    'tenant_id' => (int) $tenantId,
                    'client_key' => $clientKey,
                    'client_name' => $clientName,
                    'client_secret_hash' => $plainSecret === null ? null : $this->passwords->hash($plainSecret),
                    'status' => ClientStatus::ACTIVE->value,
                    'allowed_grant_types' => $grants,
                    'allowed_scopes' => $scopes,
                    'redirect_uris' => $redirectUris,
                    'is_confidential' => $isConfidential,
                    'is_first_party' => (bool) $this->option('first-party'),
                    'secret_rotated_at' => $plainSecret === null ? null : $this->clock->now(),
                    'expires_at' => $expiresAt,
                    'row_version' => 1,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);
            return $this->fail('The OAuth client could not be created. Verify uniqueness and tenant readiness.');
        }

        $this->info('OAuth client created. ID: '.(string) $client->getKey());

        return self::SUCCESS;
    }

    private function confidentialSecret(bool $isConfidential): ?string
    {
        if (! $isConfidential) {
            return null;
        }

        $secret = (string) $this->secret('Enter the confidential client secret (minimum 32 characters)');
        $confirmation = (string) $this->secret('Confirm the confidential client secret');
        if (mb_strlen($secret) < 32) {
            $this->error('The confidential client secret must be at least 32 characters.');

            return null;
        }
        if (! hash_equals($secret, $confirmation)) {
            $this->error('The confidential client secret confirmation does not match.');

            return null;
        }

        return $secret;
    }

    /** @return list<string> */
    private function csv(string $value): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (string $item): string => trim($item), explode(',', $value)),
            static fn (string $item): bool => $item !== '',
        )));
    }

    private function parseExpiry(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        try {
            $expiry = new DateTimeImmutable((string) $value);
            return $expiry > $this->clock->now() ? $expiry : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function fail(string $message): int
    {
        $this->error($message);
        return self::FAILURE;
    }
}
