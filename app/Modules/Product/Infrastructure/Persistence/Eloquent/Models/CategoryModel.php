<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class CategoryModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'categories';

    protected $fillable = [
        'tenant_id', 'parent_id', 'name', 'slug', 'description',
        'path', 'level', 'is_active',
    ];

    protected $casts = [
        'level'     => 'integer',
        'is_active' => 'boolean',
    ];
}
