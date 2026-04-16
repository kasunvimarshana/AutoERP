<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * Data Transfer Object for tenant configuration updates.
 *
 * Encapsulates partial configuration updates for tenants.
 * Used in PATCH operations to update specific configuration aspects
 * without modifying the entire tenant entity.
 */
class TenantConfigData extends BaseDto
{
    /**
     * Database connection configuration (optional).
     * Only updated if provided.
     */
    public ?array $databaseConfig = null;

    /**
     * Mail service configuration (optional).
     * Only updated if provided.
     */
    public ?array $mailConfig = null;

    /**
     * Cache store configuration (optional).
     * Only updated if provided.
     */
    public ?array $cacheConfig = null;

    /**
     * Queue driver configuration (optional).
     * Only updated if provided.
     */
    public ?array $queueConfig = null;

    /**
     * Feature flags for capability toggling (optional).
     * Only updated if provided.
     */
    public ?array $featureFlags = null;

    /**
     * API keys for third-party integrations (optional).
     * Only updated if provided.
     */
    public ?array $apiKeys = null;

    /**
     * Miscellaneous tenant settings (optional).
     * Only updated if provided.
     */
    public ?array $settings = null;

    /**
     * Validation rules for input data.
     *
     * At least one configuration must be provided.
     * All fields are optional but individual sub-fields are validated when present.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'databaseConfig' => 'sometimes|array',
            'databaseConfig.driver' => 'required_with:databaseConfig|string|in:mysql,pgsql,sqlite,sqlsrv',
            'databaseConfig.host' => 'required_with:databaseConfig|string|max:255',
            'databaseConfig.port' => 'required_with:databaseConfig|integer|min:1|max:65535',
            'databaseConfig.database' => 'required_with:databaseConfig|string|max:255',
            'databaseConfig.username' => 'required_with:databaseConfig|string|max:255',
            'databaseConfig.password' => 'required_with:databaseConfig|string',
            'mailConfig' => 'sometimes|array',
            'mailConfig.driver' => 'required_with:mailConfig|string|in:smtp,sendmail,mailgun,ses,log,array',
            'mailConfig.host' => 'required_with:mailConfig|string|max:255',
            'mailConfig.port' => 'required_with:mailConfig|integer|min:1|max:65535',
            'mailConfig.username' => 'required_with:mailConfig|string|max:255',
            'mailConfig.password' => 'required_with:mailConfig|string',
            'mailConfig.from' => 'required_with:mailConfig|email|max:255',
            'cacheConfig' => 'sometimes|array',
            'cacheConfig.driver' => 'required_with:cacheConfig|string|in:file,array,database,memcached,redis',
            'queueConfig' => 'sometimes|array',
            'queueConfig.driver' => 'required_with:queueConfig|string|in:database,redis,beanstalkd,sqs,fifo,null',
            'featureFlags' => 'sometimes|array',
            'apiKeys' => 'sometimes|array',
            'settings' => 'sometimes|array',
        ];
    }

    /**
     * Check if any configuration has been provided for update.
     *
     * @return bool True if at least one configuration is not null.
     */
    public function hasAnyConfig(): bool
    {
        return $this->databaseConfig !== null
            || $this->mailConfig !== null
            || $this->cacheConfig !== null
            || $this->queueConfig !== null
            || $this->featureFlags !== null
            || $this->apiKeys !== null
            || $this->settings !== null;
    }

    /**
     * Get only the configuration values that are being updated.
     * Useful for partial updates.
     *
     * @return array<string, mixed>
     */
    public function getConfigValues(): array
    {
        return array_filter([
            'databaseConfig' => $this->databaseConfig,
            'mailConfig' => $this->mailConfig,
            'cacheConfig' => $this->cacheConfig,
            'queueConfig' => $this->queueConfig,
            'featureFlags' => $this->featureFlags,
            'apiKeys' => $this->apiKeys,
            'settings' => $this->settings,
        ], fn ($value) => $value !== null);
    }
}
