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
    public ?string $logo_path = null;

    /**
     * Database connection configuration (required).
     */
    public array $database_config = [];

    /**
     * Mail service configuration (optional).
     */
    public ?array $mail_config = null;

    /**
     * Cache store configuration (optional).
     */
    public ?array $cache_config = null;

    /**
     * Queue driver configuration (optional).
     */
    public ?array $queue_config = null;

    /**
     * Feature flags for capability toggling (optional).
     */
    public ?array $feature_flags = null;

    /**
     * API keys for third-party integrations (optional).
     */
    public ?array $api_keys = null;

    /**
     * Miscellaneous tenant settings (optional).
     */
    public ?array $settings = null;

    /**
     * Subscription plan identifier.
     */
    public string $plan = 'free';

    /**
     * Tenant plan relationship ID (optional foreign key).
     */
    public ?int $tenant_plan_id = null;

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
    public ?string $trial_ends_at = null;

    /**
     * When subscription ends (optional).
     */
    public ?string $subscription_ends_at = null;

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
            'logo_path' => 'nullable|string|max:1000',
            'database_config' => 'required|array',
            'database_config.driver' => 'required|string|in:mysql,pgsql,sqlite,sqlsrv',
            'database_config.host' => 'required|string|max:255',
            'database_config.port' => 'required|integer|min:1|max:65535',
            'database_config.database' => 'required|string|max:255',
            'database_config.username' => 'required|string|max:255',
            'database_config.password' => 'required|string',
            'mail_config' => 'nullable|array',
            'mail_config.driver' => 'nullable|string|in:smtp,sendmail,mailgun,ses,log,array',
            'mail_config.host' => 'nullable|string|max:255',
            'mail_config.port' => 'nullable|integer|min:1|max:65535',
            'cache_config' => 'nullable|array',
            'queue_config' => 'nullable|array',
            'feature_flags' => 'nullable|array',
            'api_keys' => 'nullable|array',
            'settings' => 'nullable|array',
            'plan' => 'required|string|max:100',
            'tenant_plan_id' => 'nullable|exists:tenant_plans,id',
            'status' => 'required|in:active,suspended,pending,cancelled',
            'active' => 'required|boolean',
            'trial_ends_at' => 'nullable|date_format:Y-m-d H:i:s',
            'subscription_ends_at' => 'nullable|date_format:Y-m-d H:i:s',
        ];
    }

    /**
     * Return a payload suitable for persistence in the repository layer.
     *
     * @return array<string, mixed>
     */
    public function toPersistenceArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'logo_path' => $this->logo_path,
            'database_config' => $this->database_config,
            'mail_config' => $this->mail_config,
            'cache_config' => $this->cache_config,
            'queue_config' => $this->queue_config,
            'feature_flags' => $this->feature_flags ?? [],
            'api_keys' => $this->api_keys ?? [],
            'settings' => $this->settings,
            'plan' => $this->plan,
            'tenant_plan_id' => $this->tenant_plan_id,
            'status' => $this->status,
            'active' => $this->active,
            'trial_ends_at' => $this->trial_ends_at,
            'subscription_ends_at' => $this->subscription_ends_at,
        ];
    }
}
