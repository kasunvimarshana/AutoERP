<?php

namespace Modules\QualityControl\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class InspectionModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'qc_inspections';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'quality_point_id',
        'reference_no',
        'product_id',
        'lot_number',
        'qty_inspected',
        'qty_failed',
        'status',
        'inspector_id',
        'notes',
        'inspected_at',
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
