<?php

namespace Modules\Manufacturing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class BomLineModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'mfg_bom_lines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'bom_id',
        'component_product_id',
        'component_name',
        'quantity',
        'unit',
        'scrap_rate',
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

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BomModel::class, 'bom_id');
    }
}
