<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AssetModel - Eloquent model for assets table
 *
 * @package Modules\Asset\Infrastructure\Persistence\Eloquent\Models
 */
class AssetModel extends Model
{
    use SoftDeletes;

    protected $table = 'assets';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'asset_owner_id',
        'name',
        'type',
        'serial_number',
        'purchase_date',
        'acquisition_cost',
        'status',
        'depreciation_method',
        'useful_life_years',
        'salvage_value',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(AssetOwnerModel::class, 'asset_owner_id', 'id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocumentModel::class, 'asset_id', 'id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciationModel::class, 'asset_id', 'id');
    }

    public function vehicle()
    {
        return $this->hasOne(VehicleModel::class, 'asset_id', 'id');
    }

    // Scopes
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByOwner($query, string $ownerId)
    {
        return $query->where('asset_owner_id', $ownerId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'maintenance']);
    }
}
