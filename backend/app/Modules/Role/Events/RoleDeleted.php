<?php

namespace App\Modules\Role\Events;

use App\Modules\Role\Models\Role;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Role $role
    ) {}
}
