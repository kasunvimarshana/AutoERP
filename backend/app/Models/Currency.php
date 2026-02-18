<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_base',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBaseCurrency($query)
    {
        return $query->where('is_base', true);
    }
}
