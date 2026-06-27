<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\PrivateObject\Models\PrivateObject;

final class RentalCustodyEventDocument extends TenantOwnedModel
{
    protected $table = 'rental_custody_event_documents';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tenant_id'=>'integer','organization_unit_id'=>'integer','custody_event_id'=>'integer','private_object_id'=>'integer','created_by'=>'integer'];
    }

    public function custodyEvent(): BelongsTo { return $this->belongsTo(RentalCustodyEvent::class, 'custody_event_id'); }
    public function privateObject(): BelongsTo { return $this->belongsTo(PrivateObject::class, 'private_object_id'); }
}
