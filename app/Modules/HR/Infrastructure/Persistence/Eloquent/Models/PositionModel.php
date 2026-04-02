<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class PositionModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'hr_positions';

    protected $fillable = [
        'tenant_id', 'name', 'code', 'description', 'grade',
        'department_id', 'metadata', 'is_active',
    ];

    protected $casts = [
        'tenant_id'     => 'integer',
        'department_id' => 'integer',
        'is_active'     => 'boolean',
        'metadata'      => 'array',
    ];
}
