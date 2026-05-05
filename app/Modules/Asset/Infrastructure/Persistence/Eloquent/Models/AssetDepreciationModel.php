<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AssetDepreciationModel - Eloquent model for asset_depreciations table
 *
 * @package Modules\Asset\Infrastructure\Persistence\Eloquent\Models
 */
class AssetDepreciationModel extends Model
{
    use SoftDeletes;

    protected $table = 'asset_depreciations';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'asset_id',
        'year',
        'month',
        'original_cost',
        'salvage_value',
        'depreciation_method',
        'useful_life_years',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'journal_entry_id',
        'posting_status',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_id', 'id');
    }

    // Scopes
    public function scopeByAsset($query, string $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopePending($query)
    {
        return $query->where('posting_status', 'pending');
    }

    public function scopePosted($query)
    {
        return $query->where('posting_status', 'posted');
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('year')->orderByDesc('month');
    }
}
