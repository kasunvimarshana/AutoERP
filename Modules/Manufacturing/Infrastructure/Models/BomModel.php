<?php

namespace Modules\Manufacturing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class BomModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'mfg_boms';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'product_id',
        'product_name',
        'version',
        'quantity',
        'unit',
        'status',
        'notes',
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
        return $this->hasMany(BomLineModel::class, 'bom_id');
    }
}
