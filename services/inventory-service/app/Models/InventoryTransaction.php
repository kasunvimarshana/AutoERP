<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    // Transactions are immutable audit records — never updated.
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'inventory_item_id',
        'type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reserved_before',
        'reserved_change',
        'reserved_after',
        'reason',
        'reference_type',
        'reference_id',
        'performed_by',
        'metadata',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_change' => 'integer',
        'quantity_after'  => 'integer',
        'reserved_before' => 'integer',
        'reserved_change' => 'integer',
        'reserved_after'  => 'integer',
        'metadata'        => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Transaction Types
    |--------------------------------------------------------------------------
    */

    const TYPE_ADD       = 'add';
    const TYPE_SUBTRACT  = 'subtract';
    const TYPE_SET       = 'set';
    const TYPE_RESERVE   = 'reserve';
    const TYPE_RELEASE   = 'release';
    const TYPE_ADJUSTMENT = 'adjustment';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForTenant($query, int $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOfType($query, string $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', $type);
    }
}
