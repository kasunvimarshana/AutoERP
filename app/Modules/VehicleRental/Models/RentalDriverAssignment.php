<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\VehicleRental\Enums\RentalDriverAssignmentStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalDriverAssignment extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'rental_driver_assignments';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','agreement_id'=>'integer','vehicle_allocation_id'=>'integer','employee_id'=>'integer','assigned_from'=>'datetime','assigned_to'=>'datetime','is_primary'=>'boolean','status'=>RentalDriverAssignmentStatus::class,'metadata'=>'array']; }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function allocation(): BelongsTo { return $this->belongsTo(RentalVehicleAllocation::class, 'vehicle_allocation_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
}
