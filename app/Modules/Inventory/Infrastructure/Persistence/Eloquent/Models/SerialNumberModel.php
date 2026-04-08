<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SerialNumberModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'serial_numbers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'variant_id',
        'serial_number',
        'status',
        'location_id',
        'manufacture_date',
        'expiry_date',
        'metadata',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date'      => 'date',
        'metadata'         => 'array',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
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
}
