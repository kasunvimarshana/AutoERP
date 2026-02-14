<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockLedger Model
 * 
 * Append-only stock ledger for immutable audit trail
 * NO updated_at timestamp - records are never updated
 */
class StockLedger extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Disable updated_at timestamp - this is an append-only ledger
     */
    public const UPDATED_AT = null;

    protected $table = 'stock_ledger';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'batch_number',
        'lot_number',
        'serial_number',
        'manufacture_date',
        'expiry_date',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the product for this ledger entry
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this ledger entry
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the location for this ledger entry
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    /**
     * Get the user who created this ledger entry
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
