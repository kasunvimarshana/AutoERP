<?php

declare(strict_types=1);

namespace Modules\IAM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionAssignedToUser
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly array $permissionIds,
        public readonly string $type
    ) {}
}
