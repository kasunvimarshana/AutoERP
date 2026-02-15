<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\MasterData\Models\TaxRate;

class TaxGroupRate extends Model
{
    protected $fillable = [
        'tax_group_id',
        'tax_rate_id',
        'sequence',
        'apply_on_previous',
        'is_active',
    ];

    protected $casts = [
        'apply_on_previous' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function shouldApplyOnPrevious(): bool
    {
        return $this->apply_on_previous;
    }
}
