<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TenantData extends BaseDto
{
    public ?int $tenant_id;

    public string $name;

    public ?string $domain;

    public array $database_config;

    public ?array $mail_config;

    public ?array $cache_config;

    public ?array $queue_config;

    public array $feature_flags;

    public array $api_keys;

    public bool $active;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|unique:tenants,domain',
            'database_config' => 'required|array',
            'database_config.driver' => 'required|string|in:mysql,pgsql,sqlite',
            'database_config.host' => 'required|string',
            'database_config.port' => 'required|integer',
            'database_config.database' => 'required|string',
            'database_config.username' => 'required|string',
            'database_config.password' => 'required|string',
            'mail_config' => 'nullable|array',
            'cache_config' => 'nullable|array',
            'queue_config' => 'nullable|array',
            'feature_flags' => 'nullable|array',
            'api_keys' => 'nullable|array',
            'active' => 'boolean',
        ];
    }
}
