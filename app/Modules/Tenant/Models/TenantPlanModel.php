<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\ReferenceData\Models\CurrencyModel;

final class TenantPlanModel extends CoreModel
{
    protected $table = 'tenant_plans';

    protected $fillable = [
        'name', 'slug', 'features', 'limits', 'price', 'currency_id',
        'billing_interval', 'is_active', 'metadata', 'row_version', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'features' => 'array',
            'limits' => 'array',
            'currency_id' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ]);
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
