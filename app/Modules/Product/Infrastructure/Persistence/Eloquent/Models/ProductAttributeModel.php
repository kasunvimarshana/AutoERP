<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ProductAttributeModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'attributes';

    protected $fillable = [
        'tenant_id',
        'group_id',
        'name',
        'type',
        'is_required',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'group_id' => 'integer',
        'is_required' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeGroupModel::class, 'group_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValueModel::class, 'attribute_id');
    }
}
