<?php

namespace Modules\Recruitment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class JobApplicationModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'recruitment_job_applications';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'position_id',
        'candidate_name',
        'email',
        'phone',
        'resume_url',
        'cover_letter',
        'source',
        'status',
        'reviewer_id',
        'rejection_reason',
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
