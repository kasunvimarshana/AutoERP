<?php

namespace App\Modules\Finance\Models;

use App\Models\Tenant;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected static function newFactory()
    {
        return \Database\Factories\Finance\CostCenterFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
