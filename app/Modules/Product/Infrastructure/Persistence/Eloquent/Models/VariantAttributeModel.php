<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class VariantAttributeModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'variant_attributes';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'attribute_id',
        'is_required',
        'is_variation_axis',
        'display_order',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'attribute_id' => 'integer',
        'is_required' => 'boolean',
        'is_variation_axis' => 'boolean',
        'display_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeModel::class, 'attribute_id');
    }
}
