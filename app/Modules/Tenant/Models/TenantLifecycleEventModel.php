<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantLifecycleEventModel extends Model
{
    public $timestamps = false;
    protected $table = 'tenant_lifecycle_events';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'actor_id' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
