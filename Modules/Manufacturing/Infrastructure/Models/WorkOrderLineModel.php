<?php

namespace Modules\Manufacturing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class WorkOrderLineModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'mfg_work_order_lines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'work_order_id',
        'component_product_id',
        'component_name',
        'quantity_required',
        'quantity_consumed',
        'unit',
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

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrderModel::class, 'work_order_id');
    }
}
