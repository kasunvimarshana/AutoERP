<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierUserAccountModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'supplier_user_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'user_id' => 'integer',
            'is_primary' => 'boolean',
            'linked_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
