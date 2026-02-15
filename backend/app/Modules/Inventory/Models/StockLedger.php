<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariant;
use App\Modules\Tenant\Models\Branch;
use App\Modules\Tenant\Models\Location;
use App\Models\User;

class StockLedger extends Model
{
    use HasFactory, HasUuid, TenantScoped, Auditable;

    /**
     * Indicates if the model should be timestamped.
     * Stock ledger is append-only and immutable.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'variant_id',
        'branch_id',
        'location_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'quantity',
        'running_balance',
        'batch_number',
        'serial_number',
        'lot_number',
        'expiry_date',
        'unit_cost',
        'total_cost',
        'valuation_method',
        'notes',
        'metadata',
        'created_by',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:4',
        'running_balance' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Disable updates for this model (append-only).
     */
    public static function boot()
    {
        parent::boot();
        
        // Prevent updates to stock ledger entries
        static::updating(function ($model) {
            return false;
        });
    }

    /**
     * Get the product associated with this stock ledger entry.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant associated with this stock ledger entry.
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the branch for this stock ledger entry.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the location for this stock ledger entry.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user who created this entry.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if transaction increases stock.
     *
     * @return bool
     */
    public function isStockIncrease(): bool
    {
        return in_array($this->transaction_type, [
            'purchase',
            'return',
            'adjustment_increase',
            'production',
            'transfer_in',
        ]);
    }

    /**
     * Check if transaction decreases stock.
     *
     * @return bool
     */
    public function isStockDecrease(): bool
    {
        return in_array($this->transaction_type, [
            'sale',
            'return_outbound',
            'adjustment_decrease',
            'consumption',
            'transfer_out',
        ]);
    }

    /**
     * Get the absolute quantity (always positive).
     *
     * @return float
     */
    public function getAbsoluteQuantity(): float
    {
        return abs($this->quantity);
    }

    /**
     * Get current stock balance for a product at a location.
     *
     * @param int $productId
     * @param int|null $branchId
     * @param int|null $locationId
     * @param int|null $variantId
     * @return float
     */
    public static function getCurrentBalance(
        int $productId,
        ?int $branchId = null,
        ?int $locationId = null,
        ?int $variantId = null
    ): float {
        $query = static::where('product_id', $productId);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($variantId) {
            $query->where('variant_id', $variantId);
        }
        
        return $query->latest('id')->value('running_balance') ?? 0;
    }

    /**
     * Get stock movements within a date range.
     *
     * @param int $productId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int|null $branchId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMovements(
        int $productId,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $branchId = null
    ) {
        $query = static::where('product_id', $productId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->get();
    }

    /**
     * Get items nearing expiry.
     *
     * @param int $daysThreshold
     * @param int|null $branchId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getExpiringItems(int $daysThreshold = 30, ?int $branchId = null)
    {
        $thresholdDate = now()->addDays($daysThreshold);
        
        $query = static::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $thresholdDate)
            ->where('expiry_date', '>=', now())
            ->where('running_balance', '>', 0)
            ->orderBy('expiry_date');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->get();
    }

    /**
     * Calculate average cost for a product using FIFO method.
     *
     * @param int $productId
     * @param int|null $branchId
     * @return float
     */
    public static function calculateAverageCost(int $productId, ?int $branchId = null): float
    {
        $query = static::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->whereNotNull('unit_cost');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $totalCost = $query->sum('total_cost');
        $totalQuantity = $query->sum('quantity');
        
        if ($totalQuantity == 0) {
            return 0;
        }
        
        return round($totalCost / $totalQuantity, 2);
    }
}
