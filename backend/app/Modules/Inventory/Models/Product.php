<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Product Model
 * 
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $description
 * @property string $category
 * @property string $brand
 * @property string $unit_of_measure
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * 
 * @package App\Modules\Inventory\Models
 */
class Product extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category',
        'brand',
        'unit_of_measure',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'category', 'brand', 'unit_of_measure', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function skus()
    {
        return $this->hasMany(SKU::class);
    }
}
