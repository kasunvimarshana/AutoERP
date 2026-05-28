<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CustomerCategoryModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'customer_categories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'parent_id' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}