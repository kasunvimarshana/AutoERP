<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class WarehouseModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'code',
        'type',
        'address',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'id'         => 'integer',
        'tenant_id'  => 'integer',
        'address'    => 'array',
        'is_active'  => 'boolean',
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function locations(): HasMany
    {
        return $this->hasMany(LocationModel::class, 'warehouse_id');
    }
}
