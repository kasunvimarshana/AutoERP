<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateTenantData extends BaseDto
{
    public ?string $name = null;
    public ?string $status = null;
    public ?string $plan_type = null;
    public ?array $settings = null;
    public ?string $trial_ends_at = null;
}
