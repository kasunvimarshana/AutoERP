<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\CoreModel;

final class TenantPlanModel extends CoreModel
{
    protected $table = 'tenant_plans';

    protected $fillable = [
        'name', 'slug', 'is_active', 'metadata', 'row_version', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ]);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(TenantPlanRevisionModel::class, 'tenant_plan_id')
            ->orderByDesc('revision_number');
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(TenantPlanRevisionModel::class, 'tenant_plan_id')
            ->ofMany('revision_number', 'max');
    }
}
