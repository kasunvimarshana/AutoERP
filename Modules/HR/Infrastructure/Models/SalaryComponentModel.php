<?php

namespace Modules\HR\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class SalaryComponentModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'hr_salary_components';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'code',
        'type',
        'default_amount',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'default_amount' => 'string',
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
