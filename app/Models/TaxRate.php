<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'rate', 'type', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'string', // string for BCMath precision
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subTaxes(): BelongsToMany
    {
        return $this->belongsToMany(
            TaxRate::class,
            'tax_rate_sub_taxes',
            'tax_rate_id',
            'sub_tax_id'
        )->withTimestamps();
    }

    public function parentGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            TaxRate::class,
            'tax_rate_sub_taxes',
            'sub_tax_id',
            'tax_rate_id'
        )->withTimestamps();
    }
}
