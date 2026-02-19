<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * StockLocation Model
 *
 * Represents bin/shelf locations within warehouses for detailed inventory tracking.
 * Supports hierarchical location structures (e.g., Aisle-Bay-Shelf-Bin).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $warehouse_id
 * @property string|null $parent_location_id
 * @property string $code
 * @property string $name
 * @property string|null $aisle
 * @property string|null $bay
 * @property string|null $shelf
 * @property string|null $bin
 * @property bool $is_active
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class StockLocation extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'parent_location_id',
        'code',
        'name',
        'aisle',
        'bay',
        'shelf',
        'bin',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the warehouse that owns the location.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the parent location.
     */
    public function parentLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'parent_location_id');
    }

    /**
     * Get the child locations.
     */
    public function childLocations(): HasMany
    {
        return $this->hasMany(StockLocation::class, 'parent_location_id');
    }

    /**
     * Get the stock items in this location.
     */
    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class, 'location_id');
    }

    /**
     * Check if location is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the full location path (e.g., "Aisle-A > Bay-1 > Shelf-2").
     */
    public function getFullPath(): string
    {
        $parts = array_filter([
            $this->aisle ? "Aisle-{$this->aisle}" : null,
            $this->bay ? "Bay-{$this->bay}" : null,
            $this->shelf ? "Shelf-{$this->shelf}" : null,
            $this->bin ? "Bin-{$this->bin}" : null,
        ]);

        return $parts ? implode(' > ', $parts) : $this->name;
    }
}
