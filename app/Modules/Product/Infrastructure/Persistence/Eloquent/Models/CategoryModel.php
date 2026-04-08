<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class CategoryModel extends BaseModel
{
    use HasTenant, HasUuid;

    protected $table = 'categories';

    protected $fillable = [
        'uuid', 'tenant_id', 'parent_id', 'name', 'slug', 'description',
        'image_path', 'is_active', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'metadata'   => 'array',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'category_id');
    }
}
