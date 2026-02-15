<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class TaxRate extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'code',
        'type',
        'rate',
        'is_compound',
        'valid_from',
        'valid_to',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created the tax rate.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the tax rate.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if tax rate is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if tax rate is compound.
     *
     * @return bool
     */
    public function isCompound(): bool
    {
        return $this->is_compound;
    }

    /**
     * Check if tax rate is valid for given date.
     *
     * @param \DateTime|null $date
     * @return bool
     */
    public function isValidOn($date = null): bool
    {
        $date = $date ?? now();
        
        if ($this->valid_from && $date < $this->valid_from) {
            return false;
        }
        
        if ($this->valid_to && $date > $this->valid_to) {
            return false;
        }
        
        return true;
    }

    /**
     * Calculate tax amount for given base amount.
     *
     * @param float $baseAmount
     * @return float
     */
    public function calculateTax(float $baseAmount): float
    {
        return round($baseAmount * ($this->rate / 100), 2);
    }

    /**
     * Calculate total amount including tax.
     *
     * @param float $baseAmount
     * @return float
     */
    public function calculateTotalWithTax(float $baseAmount): float
    {
        return $baseAmount + $this->calculateTax($baseAmount);
    }

    /**
     * Calculate base amount from tax-inclusive amount.
     *
     * @param float $totalAmount
     * @return float
     */
    public function calculateBaseFromTotal(float $totalAmount): float
    {
        return round($totalAmount / (1 + ($this->rate / 100)), 2);
    }
}
