<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\Supplier\Models\Supplier;
use Modules\Vehicle\Enums\VehicleOwnershipType;

final class VehicleOwnership extends CoreModel
{
    public const OWNER_TYPE_CUSTOMER = 'customer';
    public const OWNER_TYPE_SUPPLIER = 'supplier';
    public const OWNER_TYPE_OWNER = 'owner';
    public const OWNER_TYPE_COMPANY = 'company';
    public const SUPPLIER_OWNER_TYPES = [self::OWNER_TYPE_SUPPLIER, self::OWNER_TYPE_OWNER];
    public const SUPPORTED_OWNER_TYPES = [
        self::OWNER_TYPE_CUSTOMER,
        self::OWNER_TYPE_SUPPLIER,
        self::OWNER_TYPE_OWNER,
        self::OWNER_TYPE_COMPANY,
    ];

    protected $table = 'vehicle_ownerships';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'owner_id' => 'integer',
            'ownership_type' => VehicleOwnershipType::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_current' => 'boolean',
        ]);
    }

    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
    public function customerOwner(): BelongsTo { return $this->belongsTo(Customer::class, 'owner_id'); }
    public function supplierOwner(): BelongsTo { return $this->belongsTo(Supplier::class, 'owner_id'); }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeForOwnerType(Builder $query, string $ownerType): Builder
    {
        return $query->where('owner_type', $ownerType);
    }
}
