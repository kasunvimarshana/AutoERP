<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\Tenant\Domain\Contracts\TenantConfigInterface;

// use Laravel\Pennant\Feature;

class TenantConfigManager implements TenantConfigManagerInterface
{
    /**
     * Apply tenant configuration to Laravel.
     */
    public function apply(TenantConfigInterface $config): void
    {
        $this->applyDatabaseConfig($config->getDatabaseConfig());
        $this->applyMailConfig($config->getMailConfig());
        $this->applyCacheConfig($config->getCacheConfig());
        $this->applyQueueConfig($config->getQueueConfig());
        $this->applyFeatureFlags($config->getFeatureFlags());
        // Add other config groups as needed
    }

    protected function applyDatabaseConfig(array $dbConfig): void
    {
        $connection = 'tenant'; // or use dynamic connection name
        Config::set("database.connections.{$connection}", $dbConfig);
        // Purge and reconnect to force new connection
        DB::purge($connection);
        DB::reconnect($connection);
    }

    protected function applyMailConfig(?array $mailConfig): void
    {
        if ($mailConfig) {
            Config::set('mail.mailers.smtp', $mailConfig);
        }
    }

    protected function applyCacheConfig(?array $cacheConfig): void
    {
        if ($cacheConfig) {
            Config::set('cache.default', $cacheConfig['driver'] ?? 'file');
            // Additional cache options can be set here
        }
    }

    protected function applyQueueConfig(?array $queueConfig): void
    {
        if ($queueConfig) {
            Config::set('queue.default', $queueConfig['driver'] ?? 'sync');
        }
    }

    protected function applyFeatureFlags(array $featureFlags): void
    {
        // If using Laravel Pennant
        foreach ($featureFlags as $flag => $value) {
            // Feature::define($flag, fn() => $value);
        }
        // Or store in a container binding
        app()->instance('tenant.feature_flags', $featureFlags);
    }
}
