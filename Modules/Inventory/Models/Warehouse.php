<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Inventory\Enums\WarehouseStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * Warehouse Model
 *
 * Represents physical storage locations for inventory.
 * Supports multi-location inventory management with address and status tracking.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $organization_id
 * @property string $code
 * @property string $name
 * @property WarehouseStatus $status
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $manager_name
 * @property bool $is_default
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Warehouse extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'code',
        'name',
        'status',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'manager_name',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'status' => WarehouseStatus::class,
        'is_default' => 'boolean',
    ];

    /**
     * Get the organization that owns the warehouse.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the stock locations in this warehouse.
     */
    public function stockLocations(): HasMany
    {
        return $this->hasMany(StockLocation::class);
    }

    /**
     * Get the stock items in this warehouse.
     */
    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    /**
     * Get the stock movements from this warehouse.
     */
    public function stockMovementsFrom(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_warehouse_id');
    }

    /**
     * Get the stock movements to this warehouse.
     */
    public function stockMovementsTo(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_warehouse_id');
    }

    /**
     * Get the stock counts for this warehouse.
     */
    public function stockCounts(): HasMany
    {
        return $this->hasMany(StockCount::class);
    }

    /**
     * Get the batch lots in this warehouse.
     */
    public function batchLots(): HasMany
    {
        return $this->hasMany(BatchLot::class);
    }

    /**
     * Get the serial numbers in this warehouse.
     */
    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class);
    }

    /**
     * Check if warehouse is active.
     */
    public function isActive(): bool
    {
        return $this->status === WarehouseStatus::ACTIVE;
    }

    /**
     * Check if warehouse can accept stock.
     */
    public function canAcceptStock(): bool
    {
        return $this->status->canAcceptStock();
    }

    /**
     * Check if warehouse can issue stock.
     */
    public function canIssueStock(): bool
    {
        return $this->status->canIssueStock();
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
