<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VehicleService\Domain\Enums\InspectionLineResult;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleServiceInspectionLine extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'vehicle_service_inspection_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'result' => InspectionLineResult::class,
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceInspection',
            'inspection_id'
        );
    }
}
