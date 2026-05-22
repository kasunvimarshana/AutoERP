<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantPlan extends Model
{
    use SoftDeletes;

    protected $table = 'tenant_plans';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'features' => 'array',
            'limits' => 'array',
            'price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant',
            'tenant_plan_id'
        );
    }
}
