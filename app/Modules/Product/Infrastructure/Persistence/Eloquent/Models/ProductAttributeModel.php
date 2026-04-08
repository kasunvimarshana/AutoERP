<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ProductAttributeModel extends Model
{
    use HasTenant, HasUuid;

    protected $table = 'product_attributes';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValueModel::class, 'attribute_id');
    }
}
