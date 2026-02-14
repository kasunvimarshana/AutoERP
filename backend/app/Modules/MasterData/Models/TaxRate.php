<?php

namespace App\Modules\MasterData\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'rate',
        'is_inclusive',
        'is_compound',
        'priority',
        'effective_from',
        'effective_to',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_inclusive' => 'boolean',
        'is_compound' => 'boolean',
        'priority' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the tax rate.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get only active tax rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get effective tax rates for a given date.
     */
    public function scopeEffectiveOn($query, $date = null)
    {
        $date = $date ?? now();
        
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $date);
        });
    }

    /**
     * Calculate tax amount for a given base amount.
     */
    public function calculateTax(float $baseAmount): float
    {
        if ($this->type === 'percentage') {
            return $baseAmount * ($this->rate / 100);
        }
        
        return $this->rate;
    }

    /**
     * Calculate amount including tax.
     */
    public function calculateTotalWithTax(float $baseAmount): float
    {
        return $baseAmount + $this->calculateTax($baseAmount);
    }

    /**
     * Calculate base amount from tax-inclusive amount.
     */
    public function calculateBaseFromTotal(float $totalAmount): float
    {
        if ($this->type === 'percentage') {
            return $totalAmount / (1 + ($this->rate / 100));
        }
        
        return $totalAmount - $this->rate;
    }
}
