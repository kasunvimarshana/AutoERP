<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class LocationModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'warehouse_id',
        'parent_id',
        'name',
        'code',
        'type',
        'path',
        'level',
        'capacity',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'id'           => 'integer',
        'tenant_id'    => 'integer',
        'warehouse_id' => 'integer',
        'parent_id'    => 'integer',
        'level'        => 'integer',
        'capacity'     => 'decimal:6',
        'is_active'    => 'boolean',
        'metadata'     => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
