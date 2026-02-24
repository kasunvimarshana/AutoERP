<?php

namespace Modules\HR\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class EmployeeModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'hr_employees';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'department_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'salary',
        'hire_date',
        'status',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
