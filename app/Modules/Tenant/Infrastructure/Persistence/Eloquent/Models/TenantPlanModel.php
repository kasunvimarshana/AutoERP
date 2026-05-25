<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class TenantPlanModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'tenant_plans';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'features' => 'array',
            'limits' => 'array',
            'price' => 'decimal:4',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ]);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(TenantModel::class, 'tenant_plan_id');
    }
}
