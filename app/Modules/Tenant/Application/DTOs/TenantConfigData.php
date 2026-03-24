<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TenantConfigData extends BaseDto
{
    public ?array $database_config;

    public ?array $mail_config;

    public ?array $cache_config;

    public ?array $queue_config;

    public ?array $feature_flags;

    public ?array $api_keys;

    public function rules(): array
    {
        return [
            'database_config' => 'nullable|array',
            'mail_config' => 'nullable|array',
            'cache_config' => 'nullable|array',
            'queue_config' => 'nullable|array',
            'feature_flags' => 'nullable|array',
            'api_keys' => 'nullable|array',
        ];
    }
}
