<?php

declare(strict_types=1);

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Repositories\AuthClientRepositoryInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Throwable;

final class AuthClientCreateCommand extends Command
{
    protected $signature = 'auth:client-create
        {tenant_id : Tenant identifier}
        {client_key : Stable client key}
        {client_name : Display name}
        {--organization_unit_id= : Optional organization unit id}
        {--provider_id= : Optional provider id}
        {--secret= : Client secret}
        {--scopes= : Comma separated scopes}
        {--grant_types=authorization_code,refresh_token,password : Comma separated grant types}
        {--redirect_uris= : Comma separated redirect URIs}';

    protected $description = 'Create a tenant-owned auth client in the Auth module.';

    public function __construct(
        private readonly AuthClientRepositoryInterface $clients,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly TenantExecutionContextInterface $tenantExecution,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = (int) $this->argument('tenant_id');
        if ($tenantId < 1) {
            $this->error('Tenant identifier must be a positive integer.');

            return self::FAILURE;
        }

        $secret = (string) ($this->option('secret') ?? '');
        if ($secret === '') {
            $this->error('Option --secret is required.');

            return self::FAILURE;
        }

        try {
            $created = $this->tenantExecution->runForTenant(
                $tenantId,
                fn () => $this->clients->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $this->toNullablePositiveInt(
                        $this->option('organization_unit_id'),
                        'Organization unit identifier',
                    ),
                    'provider_id' => $this->toNullablePositiveInt(
                        $this->option('provider_id'),
                        'Provider identifier',
                    ),
                    'client_key' => trim((string) $this->argument('client_key')),
                    'client_name' => trim((string) $this->argument('client_name')),
                    'client_secret_hash' => $this->passwordHasher->hash($secret),
                    'status' => 'active',
                    'is_confidential' => true,
                    'allowed_scopes' => $this->csvToArray((string) ($this->option('scopes') ?? '')),
                    'allowed_grant_types' => $this->csvToArray((string) $this->option('grant_types')),
                    'redirect_uris' => $this->csvToArray((string) ($this->option('redirect_uris') ?? '')),
                    'row_version' => 1,
                    'metadata' => null,
                ]),
            );
        } catch (Throwable $exception) {
            $this->error('Unable to create auth client: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Auth client created. ID: '.$created->id());

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function csvToArray(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $parts = array_map(static fn (string $item): string => trim($item), explode(',', $value));

        return array_values(array_unique(array_filter(
            $parts,
            static fn (string $item): bool => $item !== '',
        )));
    }

    private function toNullablePositiveInt(mixed $value, string $label): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new \InvalidArgumentException($label.' must be a positive integer.');
        }

        return $id;
    }
}
