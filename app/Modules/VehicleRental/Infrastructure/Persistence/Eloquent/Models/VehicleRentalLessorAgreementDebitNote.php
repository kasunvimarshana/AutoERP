<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleRentalLessorAgreementDebitNote extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'vehicle_rental_lessor_agreement_debit_notes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'amount' => 'decimal:4',
            'entry_date' => 'date',
        ];
    }

    public function lessorAgreement(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\VehicleRental\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleRentalLessorAgreement',
            'lessor_agreement_id'
        );
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'account_id'
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
