<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalReplacementStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalVehicleReplacement extends CoreModel
{
    use ScopesRentalContext;
    protected $table = 'rental_vehicle_replacements';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','agreement_id'=>'integer','old_allocation_id'=>'integer','new_allocation_id'=>'integer','replacement_at'=>'datetime','status'=>RentalReplacementStatus::class,'metadata'=>'array','completed_at'=>'datetime','reversed_at'=>'datetime']; }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function oldAllocation(): BelongsTo { return $this->belongsTo(RentalVehicleAllocation::class, 'old_allocation_id'); }
    public function newAllocation(): BelongsTo { return $this->belongsTo(RentalVehicleAllocation::class, 'new_allocation_id'); }
}
