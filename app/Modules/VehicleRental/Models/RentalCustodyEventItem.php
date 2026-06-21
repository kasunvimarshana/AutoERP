<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalCustodyItemType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalCustodyEventItem extends CoreModel
{
    use ScopesRentalContext;
    protected $table = 'rental_custody_event_items';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','custody_event_id'=>'integer','sequence'=>'integer','item_type'=>RentalCustodyItemType::class,'expected_quantity'=>'decimal:6','actual_quantity'=>'decimal:6','is_chargeable'=>'boolean','estimated_amount'=>'decimal:6','metadata'=>'array']; }
    public function custodyEvent(): BelongsTo { return $this->belongsTo(RentalCustodyEvent::class, 'custody_event_id'); }
}
