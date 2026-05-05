<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalAgreementModel extends Model
{
    use SoftDeletes;

    protected $table = 'rental_agreements';

    protected $fillable = [
        'id',
        'tenant_id',
        'reservation_id',
        'agreement_number',
        'digital_agreement_url',
        'security_deposit',
        'currency_code',
        'fuel_policy',
        'mileage_policy',
        'status',
        'signed_at',
        'version',
    ];

    protected $casts = [
        'security_deposit' => 'decimal:6',
        'signed_at' => 'datetime',
        'version' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RentalReservationModel::class, 'reservation_id', 'id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(RentalTransactionModel::class, 'agreement_id', 'id');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
