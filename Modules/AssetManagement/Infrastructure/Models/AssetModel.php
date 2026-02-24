<?php

namespace Modules\AssetManagement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class AssetModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'assets';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'asset_category_id',
        'name',
        'description',
        'serial_number',
        'location',
        'purchase_date',
        'purchase_cost',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'annual_depreciation',
        'book_value',
        'disposal_value',
        'status',
        'disposal_notes',
        'disposed_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'disposed_at'   => 'datetime',
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
