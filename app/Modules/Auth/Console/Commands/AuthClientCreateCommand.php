<?php

declare(strict_types=1);

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Repositories\AuthClientRepositoryInterface;
use Modules\Core\Contracts\PasswordHasherInterface;

final class AuthClientCreateCommand extends Command
{
    protected $signature = 'auth:client-create
        {client_key : Stable client key}
        {client_name : Display name}
        {--tenant_id= : Optional tenant id}
        {--organization_unit_id= : Optional organization unit id}
        {--provider_id= : Optional provider id}
        {--secret= : Client secret}
        {--scopes= : Comma separated scopes}
        {--grant_types=authorization_code,refresh_token,password : Comma separated grant types}
        {--redirect_uris= : Comma separated redirect URIs}';

    protected $description = 'Create an auth client in the Auth module.';

    public function __construct(
        private readonly AuthClientRepositoryInterface $clients,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $secret = (string) ($this->option('secret') ?? '');
        if ($secret === '') {
            $this->error('Option --secret is required.');

            return self::FAILURE;
        }

        $created = $this->clients->create([
            'tenant_id' => $this->toNullableInt($this->option('tenant_id')),
            'organization_unit_id' => $this->toNullableInt($this->option('organization_unit_id')),
            'provider_id' => $this->toNullableInt($this->option('provider_id')),
            'client_key' => (string) $this->argument('client_key'),
            'client_name' => (string) $this->argument('client_name'),
            'client_secret_hash' => $this->passwordHasher->hash($secret),
            'status' => 'active',
            'is_confidential' => true,
            'allowed_scopes' => $this->csvToArray((string) ($this->option('scopes') ?? '')),
            'allowed_grant_types' => $this->csvToArray((string) $this->option('grant_types')),
            'redirect_uris' => $this->csvToArray((string) ($this->option('redirect_uris') ?? '')),
            'row_version' => 1,
            'metadata' => null,
        ]);

        $this->info('Auth client created. ID: '.$created->id());

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function csvToArray(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $parts = array_map(static fn (string $item): string => trim($item), explode(',', $value));

        return array_values(array_filter($parts, static fn (string $item): bool => $item !== ''));
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
