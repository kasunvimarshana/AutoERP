<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;

final class VehicleServiceLegacyImportBatch extends TenantOwnedModel
{
    protected $table = 'vehicle_service_legacy_import_batches';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'source_job_count' => 'integer',
            'imported_job_count' => 'integer',
            'imported_line_count' => 'integer',
            'created_customer_count' => 'integer',
            'created_vehicle_count' => 'integer',
            'conflict_count' => 'integer',
            'summary' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ]);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(VehicleServiceLegacyHistory::class, 'import_batch_id');
    }
}
