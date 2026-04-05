<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CreateTenantData extends BaseDto
{
    public string $name;
    public string $slug;
    public string $status = 'trial';
    public ?string $plan_type = null;
    public ?array $settings = null;
    public ?string $trial_ends_at = null;
}
