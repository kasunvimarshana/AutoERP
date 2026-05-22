<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleRentalLesseeRunningChart extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'vehicle_rental_lessee_running_charts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'log_date' => 'date',
            'start_km' => 'decimal:4',
            'end_km' => 'decimal:4',
            'km_travelled' => 'decimal:4',
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
            'hours_used' => 'decimal:4',
            'driver_hours_normal' => 'decimal:4',
            'driver_hours_ot' => 'decimal:4',
            'driver_hours_double_ot' => 'decimal:4',
            'night_outs' => 'integer',
            'other_charges' => 'decimal:4',
            'garage_mileage' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'credit_note_total' => 'decimal:4',
        ];
    }

    public function lesseeAgreement(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\VehicleRental\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleRentalLesseeAgreement',
            'lessee_agreement_id'
        );
    }

    public function lessorAgreement(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\VehicleRental\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleRentalLessorAgreement',
            'lessor_agreement_id'
        );
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\Employee',
            'driver_id'
        );
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User',
            'created_by'
        );
    }
}
