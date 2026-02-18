<?php

namespace App\Modules\Inventory\Models;

use App\Enums\StockMovementType;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Stock Ledger Model
 *
 * Append-only stock ledger for complete inventory audit trail.
 * NEVER delete or modify existing entries - always create new entries.
 */
class StockLedger extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Inventory\StockLedgerFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'warehouse_id',
        'location_id',
        'batch_number',
        'lot_number',
        'serial_number',
        'manufacturing_date',
        'expiry_date',
        'reference_type',
        'reference_id',
        'created_by',
        'notes',
        'metadata',
        'running_balance',
        'transaction_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'movement_type' => StockMovementType::class,
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'running_balance' => 'decimal:4',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'transaction_date' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent deletion - append-only ledger
        static::deleting(function ($ledger) {
            throw new \RuntimeException('Stock ledger entries cannot be deleted. Create a reversal entry instead.');
        });

        // Calculate running balance on create
        static::creating(function ($ledger) {
            if (auth()->check()) {
                $ledger->created_by = auth()->id();
            }

            // Set transaction date if not provided
            if (! $ledger->transaction_date) {
                $ledger->transaction_date = now();
            }

            // Calculate running balance
            $previousBalance = static::where('product_id', $ledger->product_id)
                ->where('warehouse_id', $ledger->warehouse_id)
                ->latest('id')
                ->value('running_balance') ?? 0;

            $ledger->running_balance = $previousBalance + $ledger->quantity;
        });
    }

    /**
     * Get the tenant that owns the stock ledger
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the product associated with the stock ledger
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created the entry
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the reference model (polymorphic)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if this is an increase movement
     */
    public function isIncrease(): bool
    {
        return $this->movement_type->isIncrease();
    }

    /**
     * Check if this is a decrease movement
     */
    public function isDecrease(): bool
    {
        return $this->movement_type->isDecrease();
    }

    /**
     * Check if item is expired
     */
    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Check if item is near expiry (within 30 days)
     */
    public function isNearExpiry(int $days = 30): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->diffInDays(now()) <= $days && ! $this->isExpired();
    }

    /**
     * Get the value of this stock movement
     */
    public function getValue(): float
    {
        return abs($this->quantity) * ($this->unit_cost ?? 0);
    }
}
