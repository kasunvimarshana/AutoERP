<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\MasterData\Models\TaxRate;

class TaxExemption extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'exemption_number',
        'entity_type',
        'entity_id',
        'tax_rate_id',
        'tax_group_id',
        'exemption_type',
        'exemption_rate',
        'reason',
        'certificate_number',
        'valid_from',
        'valid_to',
        'is_active',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'exemption_rate' => 'decimal:4',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
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

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isFullExemption(): bool
    {
        return $this->exemption_type === 'full';
    }

    public function isPartialExemption(): bool
    {
        return $this->exemption_type === 'partial';
    }

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

    public function calculateExemptedAmount(float $taxAmount): float
    {
        if ($this->isFullExemption()) {
            return $taxAmount;
        }

        if ($this->isPartialExemption() && $this->exemption_rate) {
            return round($taxAmount * ($this->exemption_rate / 100), 4);
        }

        return 0.0;
    }
}
