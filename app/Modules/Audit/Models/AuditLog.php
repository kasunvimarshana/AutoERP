<?php

namespace App\Modules\Audit\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use UuidTrait;

    protected $fillable = ['user_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'ip_address', 'user_agent'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}