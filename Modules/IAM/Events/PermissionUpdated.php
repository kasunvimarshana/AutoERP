<?php

declare(strict_types=1);

namespace Modules\IAM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\IAM\Entities\Permission;

class PermissionUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Permission $permission
    ) {}
}
