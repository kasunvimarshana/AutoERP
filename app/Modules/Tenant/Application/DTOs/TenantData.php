<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class TenantData extends BaseDto
{
    public ?string $name = null;
    public ?string $slug = null;
    public ?string $status = null;
    public ?string $plan = null;
    public ?string $domain = null;
    public ?string $logo_path = null;
    public ?array $settings = null;
    public ?string $trial_ends_at = null;
    public ?string $subscription_ends_at = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'slug'                  => ['required', 'string', 'max:100', 'alpha_dash'],
            'status'                => ['sometimes', 'string', 'in:active,suspended,trial,cancelled'],
            'plan'                  => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'domain'                => ['nullable', 'string', 'max:255'],
            'logo_path'             => ['nullable', 'string', 'max:1000'],
            'settings'              => ['nullable', 'array'],
            'trial_ends_at'         => ['nullable', 'date'],
            'subscription_ends_at'  => ['nullable', 'date'],
            'metadata'              => ['nullable', 'array'],
        ];
    }
}
