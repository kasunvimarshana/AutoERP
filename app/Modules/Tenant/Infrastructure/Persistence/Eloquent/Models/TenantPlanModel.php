<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class TenantPlanModel extends Model
{
    use HasActiveScope, SoftDeletes;

    protected $table = 'tenant_plans';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'limits' => 'array',
            'metadata' => 'array',
            'price' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(TenantModel::class, 'tenant_plan_id');
    }

}
