<?php

namespace App\Infrastructure\Tenant;

use App\Domain\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantDatabaseManager
{
    /**
     * Establish a database connection for the given tenant.
     * Registers the connection dynamically (no restart required).
     */
    public function connectForTenant(Tenant $tenant): void
    {
        $connectionName = $tenant->getConnectionName();
        $strategy       = config('tenant.db_strategy', 'schema');

        // Register the connection only if not already configured
        if (!$this->connectionExists($connectionName)) {
            $config = $this->buildConnectionConfig($tenant, $strategy);
            Config::set("database.connections.{$connectionName}", $config);
        }

        // Set this connection as active for the current request scope
        DB::setDefaultConnection($connectionName);
    }

    /**
     * Create the database schema/database for a new tenant.
     */
    public function createSchemaForTenant(Tenant $tenant): void
    {
        $strategy = config('tenant.db_strategy', 'schema');

        match ($strategy) {
            'schema'   => $this->createSchema($tenant),
            'database' => $this->createDatabase($tenant),
            'prefix'   => null, // Shared DB — no schema creation needed
            default    => null,
        };
    }

    /**
     * Drop the schema/database for a tenant (use with caution).
     */
    public function dropForTenant(Tenant $tenant): void
    {
        $strategy = config('tenant.db_strategy', 'schema');

        match ($strategy) {
            'schema'   => $this->dropSchema($tenant),
            'database' => $this->dropDatabase($tenant),
            default    => null,
        };
    }

    /**
     * Switch the default DB connection back to the central/public schema.
     */
    public function switchToCentral(): void
    {
        DB::setDefaultConnection(config('database.default', 'pgsql'));
    }

    /**
     * Check whether a connection is already registered.
     */
    private function connectionExists(string $connectionName): bool
    {
        return Config::has("database.connections.{$connectionName}");
    }

    /**
     * Build the Eloquent/PDO connection config array for a tenant.
     */
    private function buildConnectionConfig(Tenant $tenant, string $strategy): array
    {
        $base = config('database.connections.pgsql', []);

        if ($strategy === 'schema') {
            return array_merge($base, [
                'search_path' => $tenant->getSchemaName(),
            ]);
        }

        if ($strategy === 'database') {
            return array_merge($base, [
                'database' => config('tenant.db_prefix') . $tenant->subdomain,
            ]);
        }

        // 'prefix' strategy: same DB, same schema, just a table prefix
        return array_merge($base, [
            'prefix' => config('tenant.db_prefix') . $tenant->subdomain . '_',
        ]);
    }

    /**
     * Create a PostgreSQL schema for the tenant.
     */
    private function createSchema(Tenant $tenant): void
    {
        $schemaName = $tenant->getSchemaName();

        // Use the central connection to create the schema
        DB::connection(config('database.default'))->statement(
            "CREATE SCHEMA IF NOT EXISTS \"{$schemaName}\""
        );

        Log::info("Created PostgreSQL schema: {$schemaName}");
    }

    /**
     * Drop a PostgreSQL schema for the tenant.
     */
    private function dropSchema(Tenant $tenant): void
    {
        $schemaName = $tenant->getSchemaName();

        DB::connection(config('database.default'))->statement(
            "DROP SCHEMA IF EXISTS \"{$schemaName}\" CASCADE"
        );

        Log::warning("Dropped PostgreSQL schema: {$schemaName}");
    }

    /**
     * Create a dedicated database for the tenant.
     */
    private function createDatabase(Tenant $tenant): void
    {
        $dbName = config('tenant.db_prefix') . $tenant->subdomain;

        DB::connection(config('database.default'))->statement(
            "CREATE DATABASE \"{$dbName}\""
        );

        Log::info("Created database: {$dbName}");
    }

    /**
     * Drop a dedicated database for the tenant.
     */
    private function dropDatabase(Tenant $tenant): void
    {
        $dbName = config('tenant.db_prefix') . $tenant->subdomain;

        DB::connection(config('database.default'))->statement(
            "DROP DATABASE IF EXISTS \"{$dbName}\""
        );

        Log::warning("Dropped database: {$dbName}");
    }
}
