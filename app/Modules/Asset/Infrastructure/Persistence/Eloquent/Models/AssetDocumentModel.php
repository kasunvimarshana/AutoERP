<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AssetDocumentModel - Eloquent model for asset_documents table
 *
 * @package Modules\Asset\Infrastructure\Persistence\Eloquent\Models
 */
class AssetDocumentModel extends Model
{
    use SoftDeletes;

    protected $table = 'asset_documents';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'asset_id',
        'document_type',
        'document_name',
        'document_number',
        'issue_date',
        'expiry_date',
        'file_path',
        'file_url',
        'issuing_authority',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
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

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeExpiring($query, int $days = 30)
    {
        $now = now();
        $threshold = $now->copy()->addDays($days);
        return $query->whereBetween('expiry_date', [$now, $threshold]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
