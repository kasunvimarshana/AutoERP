<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\VehicleService\Domain\Enums\ServiceOverallResult;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleServiceInspection extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'vehicle_service_inspections';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'overall_result' => ServiceOverallResult::class,
            'performed_at' => 'datetime',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceJobCard',
            'job_card_id'
        );
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo('App\\Models\\User', 'performed_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceInspectionLine',
            'inspection_id'
        );
    }
}
