<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ProductAttributeGroupModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'attribute_groups';

    protected $fillable = [
        'tenant_id',
        'name',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
    ];

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttributeModel::class, 'group_id');
    }
}
