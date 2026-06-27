<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalCustodyStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalCustodyEvent extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'rental_custody_events';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','vehicle_allocation_id'=>'integer','replacement_id'=>'integer','vehicle_id'=>'integer','event_type'=>RentalCustodyEventType::class,'occurred_at'=>'datetime','odometer'=>'decimal:6','fuel_level_percent'=>'decimal:4','handed_over_by_employee_id'=>'integer','received_by_employee_id'=>'integer','status'=>RentalCustodyStatus::class,'metadata'=>'array','confirmed_at'=>'datetime','reversed_at'=>'datetime']; }
    public function allocation(): BelongsTo { return $this->belongsTo(RentalVehicleAllocation::class, 'vehicle_allocation_id'); }
    public function replacement(): BelongsTo { return $this->belongsTo(RentalVehicleReplacement::class, 'replacement_id'); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
    public function handedOverByEmployee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'handed_over_by_employee_id'); }
    public function receivedByEmployee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'received_by_employee_id'); }
    public function items(): HasMany { return $this->hasMany(RentalCustodyEventItem::class, 'custody_event_id')->orderBy('sequence'); }
    public function documents(): HasMany { return $this->hasMany(RentalCustodyEventDocument::class, 'custody_event_id')->latest('id'); }
}
