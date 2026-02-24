<?php

namespace Modules\Reporting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class DashboardModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'reporting_dashboards';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'name',
        'description',
        'layout',
        'is_shared',
        'refresh_seconds',
    ];

    protected $casts = [
        'layout'    => 'array',
        'is_shared' => 'boolean',
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
