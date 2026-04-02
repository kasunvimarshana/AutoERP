<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class DepartmentModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'hr_departments';

    protected $fillable = [
        'tenant_id', 'name', 'code', 'description', 'manager_id',
        'parent_id', 'lft', 'rgt', 'metadata', 'is_active',
    ];

    protected $casts = [
        'tenant_id'  => 'integer',
        'manager_id' => 'integer',
        'parent_id'  => 'integer',
        'lft'        => 'integer',
        'rgt'        => 'integer',
        'is_active'  => 'boolean',
        'metadata'   => 'array',
    ];
}
