<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Factories;

use Modules\Core\Domain\ValueObjects\ApiKeys;
use Modules\Core\Domain\ValueObjects\CacheConfig;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Core\Domain\ValueObjects\FeatureFlags;
use Modules\Core\Domain\ValueObjects\MailConfig;
use Modules\Core\Domain\ValueObjects\QueueConfig;

class TenantConfigValueObjectFactory
{
    public function databaseConfig(array $payload, array $fallback = []): DatabaseConfig
    {
        return DatabaseConfig::fromArray($payload['database_config'] ?? $fallback);
    }

    public function mailConfig(array $payload, ?array $fallback = null): ?MailConfig
    {
        if (array_key_exists('mail_config', $payload)) {
            $value = $payload['mail_config'];

            if (is_array($value) && $value !== []) {
                return MailConfig::fromArray($value);
            }

            return null;
        }

        return $fallback !== null ? MailConfig::fromArray($fallback) : null;
    }

    public function cacheConfig(array $payload, ?array $fallback = null): ?CacheConfig
    {
        if (array_key_exists('cache_config', $payload)) {
            $value = $payload['cache_config'];

            if (is_array($value) && $value !== []) {
                return CacheConfig::fromArray($value);
            }

            return null;
        }

        return $fallback !== null ? CacheConfig::fromArray($fallback) : null;
    }

    public function queueConfig(array $payload, ?array $fallback = null): ?QueueConfig
    {
        if (array_key_exists('queue_config', $payload)) {
            $value = $payload['queue_config'];

            if (is_array($value) && $value !== []) {
                return QueueConfig::fromArray($value);
            }

            return null;
        }

        return $fallback !== null ? QueueConfig::fromArray($fallback) : null;
    }

    public function featureFlags(array $payload, array $fallback = []): FeatureFlags
    {
        return new FeatureFlags($payload['feature_flags'] ?? $fallback);
    }

    public function apiKeys(array $payload, array $fallback = []): ApiKeys
    {
        return new ApiKeys($payload['api_keys'] ?? $fallback);
    }
}
