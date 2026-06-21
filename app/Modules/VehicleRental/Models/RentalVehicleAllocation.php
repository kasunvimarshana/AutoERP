<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\CoreModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleOwnership;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalVehicleSourceType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalVehicleAllocation extends CoreModel
{
    use ScopesRentalContext;
    protected $table = 'rental_vehicle_allocations';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','agreement_id'=>'integer','vehicle_id'=>'integer','vehicle_ownership_id'=>'integer','vehicle_source_type'=>RentalVehicleSourceType::class,'source_allocation_id'=>'integer','vehicle_finance_agreement_id'=>'integer','replaces_allocation_id'=>'integer','allocated_from'=>'datetime','allocated_to'=>'datetime','actual_returned_at'=>'datetime','start_odometer'=>'decimal:6','end_odometer'=>'decimal:6','status'=>RentalAllocationStatus::class,'metadata'=>'array','activated_at'=>'datetime','closed_at'=>'datetime']; }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
    public function ownership(): BelongsTo { return $this->belongsTo(VehicleOwnership::class, 'vehicle_ownership_id'); }
    public function sourceAllocation(): BelongsTo { return $this->belongsTo(self::class, 'source_allocation_id'); }
    public function customerAllocations(): HasMany { return $this->hasMany(self::class, 'source_allocation_id'); }
    public function financeAgreement(): BelongsTo { return $this->belongsTo(VehicleFinanceAgreement::class, 'vehicle_finance_agreement_id'); }
    public function replacesAllocation(): BelongsTo { return $this->belongsTo(self::class, 'replaces_allocation_id'); }
    public function replacementAllocation(): HasOne { return $this->hasOne(self::class, 'replaces_allocation_id'); }
    public function driverAssignments(): HasMany { return $this->hasMany(RentalDriverAssignment::class, 'vehicle_allocation_id'); }
    public function custodyEvents(): HasMany { return $this->hasMany(RentalCustodyEvent::class, 'vehicle_allocation_id')->orderBy('occurred_at'); }
    public function usageLogs(): HasMany { return $this->hasMany(RentalUsageLog::class, 'vehicle_allocation_id'); }
}
