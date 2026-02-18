<?php

namespace App\Modules\Finance\Models;

use App\Models\Tenant;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FiscalYear extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected static function newFactory()
    {
        return \Database\Factories\Finance\FiscalYearFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'start_date',
        'end_date',
        'is_closed',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('is_closed', false);
    }
}
