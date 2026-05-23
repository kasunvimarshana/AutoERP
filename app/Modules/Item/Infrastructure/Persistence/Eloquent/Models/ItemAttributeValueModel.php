<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class ItemAttributeValueModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope, SoftDeletes;

    protected $table = 'item_attribute_values';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attribute_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'sort_order' => 'integer',
            'tenant_id' => 'integer',
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

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ItemAttributeModel::class, 'attribute_id');
    }

    public function itemVariantAttributeValues(): HasMany
    {
        return $this->hasMany(ItemVariantAttributeValueModel::class, 'attribute_value_id');
    }
}
