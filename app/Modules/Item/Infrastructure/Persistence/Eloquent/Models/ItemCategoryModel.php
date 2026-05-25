<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRuleModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemCategoryModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class ItemCategoryModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasReferenceScope, HasActiveScope, SoftDeletes;

    protected $table = 'item_categories';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ItemCategoryModel::class, 'parent_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function itemCategories(): HasMany
    {
        return $this->hasMany(ItemCategoryModel::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemModel::class, 'category_id');
    }

    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRuleModel::class, 'item_category_id');
    }

}
