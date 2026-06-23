<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;

final class VehicleDocument extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'vehicle_documents';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'document_type' => VehicleDocumentType::class,
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'status' => VehicleDocumentStatus::class,
        ]);
    }

    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
}
