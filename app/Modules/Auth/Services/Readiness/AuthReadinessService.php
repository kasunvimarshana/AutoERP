<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Readiness;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Services\AccessTokenRouter;
use Modules\Auth\Services\PlatformAuthenticationService;
use Modules\Auth\Services\PlatformAuthProfileBuilder;
use Modules\Auth\Services\TenantAuthenticationService;
use Modules\Auth\Services\TenantAuthProfileBuilder;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionDirectoryInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;
use Throwable;

final readonly class AuthReadinessService
{
    private const CACHE_PROBE_PREFIX = 'autoerp:auth-readiness:';

    public function __construct(
        private Container $container,
        private DatabaseManager $database,
        private CacheRepository $cache,
    ) {}

    /** @return array{ready:bool,checks:list<array{name:string,ready:bool,message:string}>} */
    public function inspect(): array
    {
        $checks = [];
        $checks[] = $this->check('application_key', fn (): string => $this->applicationKeyReady());
        $checks[] = $this->check('database_connection', fn (): string => $this->databaseReady());
        $checks[] = $this->check('required_schema', fn (): string => $this->schemaReady());
        $checks[] = $this->check('cache_store', fn (): string => $this->cacheReady());
        $checks[] = $this->check('platform_permission_binding', fn (): string => $this->resolveBinding(PlatformPermissionCheckerInterface::class));
        $checks[] = $this->check('platform_permission_directory_binding', fn (): string => $this->resolveBinding(PlatformPermissionDirectoryInterface::class));
        $checks[] = $this->check('tenant_directory_binding', fn (): string => $this->resolveBinding(TenantUserAuthenticationDirectoryInterface::class));
        $checks[] = $this->check('platform_directory_binding', fn (): string => $this->resolveBinding(PlatformOperatorAuthenticationDirectoryInterface::class));
        $checks[] = $this->check('tenant_login_graph', fn (): string => $this->resolveBinding(TenantAuthenticationService::class));
        $checks[] = $this->check('platform_login_graph', fn (): string => $this->resolveBinding(PlatformAuthenticationService::class));
        $checks[] = $this->check('tenant_profile_graph', fn (): string => $this->resolveBinding(TenantAuthProfileBuilder::class));
        $checks[] = $this->check('platform_profile_graph', fn (): string => $this->resolveBinding(PlatformAuthProfileBuilder::class));
        $checks[] = $this->check('access_token_router', fn (): string => $this->resolveBinding(AccessTokenRouter::class));

        return [
            'ready' => ! in_array(false, array_column($checks, 'ready'), true),
            'checks' => $checks,
        ];
    }

    private function applicationKeyReady(): string
    {
        $this->container->make(OpaqueTokenCodec::class);

        return 'Auth cryptographic key is valid.';
    }

    private function databaseReady(): string
    {
        $this->database->connection()->getPdo();

        return 'Database connection is available.';
    }

    private function schemaReady(): string
    {
        $requiredTables = [
            'tenants',
            'users',
            'user_organization_units',
            'platform_operators',
            'auth_providers',
            'auth_identities',
            'auth_user_password_credentials',
            'auth_platform_operator_password_credentials',
            'auth_sessions',
            'auth_access_tokens',
            'auth_refresh_tokens',
            'auth_platform_sessions',
            'auth_platform_access_tokens',
            'auth_platform_refresh_tokens',
            'auth_login_attempts',
            'auth_platform_login_attempts',
        ];

        $missing = [];
        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        if ($missing !== []) {
            throw new \RuntimeException('Missing required Auth tables: '.implode(', ', $missing));
        }

        return 'Required Auth schema is present.';
    }

    private function cacheReady(): string
    {
        $key = self::CACHE_PROBE_PREFIX.bin2hex(random_bytes(8));
        $this->cache->put($key, 'ready', 30);
        $value = $this->cache->get($key);
        $this->cache->forget($key);
        if ($value !== 'ready') {
            throw new \RuntimeException('Configured cache store failed its read/write probe.');
        }

        return 'Configured cache store is writable.';
    }

    private function resolveBinding(string $abstract): string
    {
        $resolved = $this->container->make($abstract);

        return sprintf('%s resolves to %s.', $abstract, $resolved::class);
    }

    /** @return array{name:string,ready:bool,message:string} */
    private function check(string $name, callable $probe): array
    {
        try {
            return ['name' => $name, 'ready' => true, 'message' => (string) $probe()];
        } catch (Throwable $exception) {
            report($exception);

            return ['name' => $name, 'ready' => false, 'message' => $exception->getMessage()];
        }
    }
}
