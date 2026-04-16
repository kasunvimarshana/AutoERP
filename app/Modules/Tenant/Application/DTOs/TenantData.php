<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * Data Transfer Object for tenant creation and update operations.
 *
 * Encapsulates tenant data with validation rules. Used for transferring
 * validated tenant information between HTTP layer and application services.
 */
class TenantData extends BaseDto
{
    /**
     * Tenant ID for updates; null for creation.
     */
    public ?int $id = null;

    /**
     * Human-readable tenant name (required).
     */
    public string $name;

    /**
     * URL-safe slug identifier (required).
     */
    public string $slug;

    /**
     * Tenant domain for domain-based routing (optional).
     */
    public ?string $domain = null;

    /**
     * Path to logo file (optional).
     */
    public ?string $logoPath = null;

    /**
     * Database connection configuration (required).
     */
    public array $databaseConfig = [];

    /**
     * Mail service configuration (optional).
     */
    public ?array $mailConfig = null;

    /**
     * Cache store configuration (optional).
     */
    public ?array $cacheConfig = null;

    /**
     * Queue driver configuration (optional).
     */
    public ?array $queueConfig = null;

    /**
     * Feature flags for capability toggling (optional).
     */
    public ?array $featureFlags = null;

    /**
     * API keys for third-party integrations (optional).
     */
    public ?array $apiKeys = null;

    /**
     * Miscellaneous tenant settings (optional).
     */
    public ?array $settings = null;

    /**
     * Subscription plan identifier (required, defaults to 'free').
     */
    public string $plan = 'free';

    /**
     * Tenant plan relationship ID (optional foreign key).
     */
    public ?int $tenantPlanId = null;

    /**
     * Tenant status: active, suspended, pending, or cancelled.
     */
    public string $status = 'active';

    /**
     * Is tenant explicitly active (distinct from status).
     */
    public bool $active = true;

    /**
     * When free trial ends (optional).
     */
    public ?string $trialEndsAt = null;

    /**
     * When subscription ends (optional).
     */
    public ?string $subscriptionEndsAt = null;

    /**
     * Validation rules for input data.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $excludeId = $this->id ? ",{$this->id}" : '';

        return [
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:tenants,slug{$excludeId}",
            'domain' => "nullable|string|max:255|unique:tenants,domain{$excludeId}",
            'logoPath' => 'nullable|string|max:1000',
            'databaseConfig' => 'required|array',
            'databaseConfig.driver' => 'required|string|in:mysql,pgsql,sqlite,sqlsrv',
            'databaseConfig.host' => 'required|string|max:255',
            'databaseConfig.port' => 'required|integer|min:1|max:65535',
            'databaseConfig.database' => 'required|string|max:255',
            'databaseConfig.username' => 'required|string|max:255',
            'databaseConfig.password' => 'required|string',
            'mailConfig' => 'nullable|array',
            'mailConfig.driver' => 'nullable|string|in:smtp,sendmail,mailgun,ses,log,array',
            'mailConfig.host' => 'nullable|string|max:255',
            'mailConfig.port' => 'nullable|integer|min:1|max:65535',
            'cacheConfig' => 'nullable|array',
            'queueConfig' => 'nullable|array',
            'featureFlags' => 'nullable|array',
            'apiKeys' => 'nullable|array',
            'settings' => 'nullable|array',
            'plan' => 'required|string|max:100',
            'tenantPlanId' => 'nullable|exists:tenant_plans,id',
            'status' => 'required|in:active,suspended,pending,cancelled',
            'active' => 'required|boolean',
            'trialEndsAt' => 'nullable|date_format:Y-m-d H:i:s',
            'subscriptionEndsAt' => 'nullable|date_format:Y-m-d H:i:s',
        ];
    }
}
