<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CustomerContactModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'customer_contacts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'customer_id' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}
