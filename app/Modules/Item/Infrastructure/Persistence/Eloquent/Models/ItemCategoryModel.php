<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRuleModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class ItemCategoryModel extends Model
{
    use HasActiveScope, HasOrganizationUnitScope, HasReferenceScope, HasTenantScope, SoftDeletes;

    protected $table = 'item_categories';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'depth' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'parent_id' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ItemCategoryModel::class, 'parent_id');
    }

    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRuleModel::class, 'item_category_id');
    }

    public function itemCategoriesAsParent(): HasMany
    {
        return $this->hasMany(ItemCategoryModel::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemModel::class, 'category_id');
    }
}

