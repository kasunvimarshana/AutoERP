<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleServiceDiagnosticLine extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'vehicle_service_diagnostic_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceDiagnostic',
            'diagnostic_id'
        );
    }
}
