<?php

namespace Modules\QualityControl\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class QualityAlertModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'qc_quality_alerts';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'inspection_id',
        'title',
        'description',
        'product_id',
        'lot_number',
        'priority',
        'status',
        'assigned_to',
        'deadline',
        'resolved_at',
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
