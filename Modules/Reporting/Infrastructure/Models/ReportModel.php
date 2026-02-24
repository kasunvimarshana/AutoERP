<?php

namespace Modules\Reporting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ReportModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'reporting_reports';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'name',
        'description',
        'type',
        'data_source',
        'fields',
        'filters',
        'group_by',
        'sort_by',
        'is_shared',
    ];

    protected $casts = [
        'fields'    => 'array',
        'filters'   => 'array',
        'group_by'  => 'array',
        'sort_by'   => 'array',
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
