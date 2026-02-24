<?php

namespace Modules\Maintenance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class EquipmentModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'maintenance_equipment';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'serial_number',
        'category',
        'location',
        'assigned_team_id',
        'purchase_date',
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
}
