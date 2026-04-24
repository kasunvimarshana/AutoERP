<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ProductAttributeValueModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'attribute_values';

    protected $fillable = [
        'tenant_id',
        'attribute_id',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'attribute_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeModel::class, 'attribute_id');
    }
}
