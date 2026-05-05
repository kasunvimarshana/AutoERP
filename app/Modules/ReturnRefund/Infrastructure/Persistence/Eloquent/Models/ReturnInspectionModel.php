<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnInspectionModel extends Model
{
    protected $table = 'return_inspections';

    protected $fillable = [
        'id',
        'tenant_id',
        'rental_transaction_id',
        'is_damaged',
        'damage_notes',
        'damage_charge',
        'fuel_adjustment_charge',
        'late_return_charge',
        'inspected_at',
    ];

    protected $casts = [
        'is_damaged' => 'boolean',
        'damage_charge' => 'decimal:6',
        'fuel_adjustment_charge' => 'decimal:6',
        'late_return_charge' => 'decimal:6',
        'inspected_at' => 'datetime',
    ];

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
