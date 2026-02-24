<?php

namespace Modules\Recruitment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class JobPositionModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'recruitment_job_positions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'title',
        'department_id',
        'location',
        'employment_type',
        'description',
        'requirements',
        'vacancies',
        'status',
        'expected_start_date',
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
