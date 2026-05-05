<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AssetOwnerModel - Eloquent model for asset_owners table
 *
 * @package Modules\Asset\Infrastructure\Persistence\Eloquent\Models
 */
class AssetOwnerModel extends Model
{
    use SoftDeletes;

    protected $table = 'asset_owners';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'owner_type',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'commission_percentage',
        'payment_terms_days',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function assets(): HasMany
    {
        return $this->hasMany(AssetModel::class, 'asset_owner_id', 'id');
    }

    // Scopes
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeThirdParty($query)
    {
        return $query->where('owner_type', 'third_party');
    }

    public function scopeCompany($query)
    {
        return $query->where('owner_type', 'company');
    }
}
