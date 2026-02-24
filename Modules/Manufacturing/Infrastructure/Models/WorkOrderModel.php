<?php

namespace Modules\Manufacturing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class WorkOrderModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'mfg_work_orders';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'bom_id',
        'reference_no',
        'quantity_planned',
        'quantity_produced',
        'status',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
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

    public function lines(): HasMany
    {
        return $this->hasMany(WorkOrderLineModel::class, 'work_order_id');
    }
}
