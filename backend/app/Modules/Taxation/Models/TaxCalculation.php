<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;

class TaxCalculation extends Model
{
    use HasFactory, HasUuid, TenantScoped;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'entity_type',
        'entity_id',
        'base_amount',
        'tax_amount',
        'total_amount',
        'is_inclusive',
        'tax_breakdown',
        'applied_taxes',
        'exemptions_applied',
        'customer_id',
        'product_id',
        'branch_id',
        'tax_jurisdiction_id',
        'calculation_method',
        'metadata',
        'calculated_at',
    ];

    protected $casts = [
        'base_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'is_inclusive' => 'boolean',
        'tax_breakdown' => 'array',
        'applied_taxes' => 'array',
        'exemptions_applied' => 'array',
        'metadata' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function taxJurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class);
    }

    public function isInclusive(): bool
    {
        return $this->is_inclusive;
    }

    public function getEffectiveTaxRate(): float
    {
        if ($this->base_amount > 0) {
            return round(($this->tax_amount / $this->base_amount) * 100, 4);
        }
        
        return 0.0;
    }
}
