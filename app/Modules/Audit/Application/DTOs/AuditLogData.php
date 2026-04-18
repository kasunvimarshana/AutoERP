<?php

declare(strict_types=1);

namespace Modules\Audit\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class AuditLogData extends BaseDto
{
    public ?int $tenant_id = null;

    public ?int $user_id = null;

    public string $event = 'custom';

    public string $auditable_type = '';

    public int|string $auditable_id = '';

    public ?array $old_values = null;

    public ?array $new_values = null;

    public ?string $url = null;

    public ?string $ip_address = null;

    public ?string $user_agent = null;

    public ?array $tags = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'event' => ['required', 'string', 'max:50'],
            'auditable_type' => ['required', 'string'],
            'auditable_id' => ['required'],
        ];
    }
}
