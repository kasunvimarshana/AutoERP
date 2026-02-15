<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\MasterData\Models\TaxRate;

class TaxGroup extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'code',
        'description',
        'application_type',
        'is_inclusive',
        'is_active',
        'effective_from',
        'effective_to',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_inclusive' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function taxGroupRates(): HasMany
    {
        return $this->hasMany(TaxGroupRate::class)->orderBy('sequence');
    }

    public function taxRates(): BelongsToMany
    {
        return $this->belongsToMany(TaxRate::class, 'tax_group_rates')
            ->withPivot(['sequence', 'apply_on_previous', 'is_active'])
            ->orderBy('sequence');
    }

    public function exemptions(): HasMany
    {
        return $this->hasMany(TaxExemption::class);
    }

    public function jurisdictions(): HasMany
    {
        return $this->hasMany(TaxJurisdiction::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isInclusive(): bool
    {
        return $this->is_inclusive;
    }

    public function isValidOn($date = null): bool
    {
        $date = $date ?? now();
        
        if ($this->effective_from && $date < $this->effective_from) {
            return false;
        }
        
        if ($this->effective_to && $date > $this->effective_to) {
            return false;
        }
        
        return true;
    }

    public function getTotalRate(): float
    {
        $total = 0.0;

        if ($this->application_type === 'compound' || $this->application_type === 'stacked') {
            foreach ($this->taxRates as $taxRate) {
                $total += $taxRate->rate;
            }
        } elseif ($this->application_type === 'highest') {
            $total = $this->taxRates->max('rate') ?? 0.0;
        } elseif ($this->application_type === 'average') {
            $count = $this->taxRates->count();
            $total = $count > 0 ? $this->taxRates->sum('rate') / $count : 0.0;
        }

        return round($total, 4);
    }
}
