<?php

declare(strict_types=1);

namespace Modules\IAM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionAssignedToRole
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $roleId,
        public readonly array $permissionIds
    ) {}
}
