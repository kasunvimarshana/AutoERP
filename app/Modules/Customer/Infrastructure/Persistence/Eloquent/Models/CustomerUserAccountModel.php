<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CustomerUserAccountModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'customer_user_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'user_id' => 'integer',
            'is_primary' => 'boolean',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'linked_user_by' => 'integer',
            'invited_by' => 'integer',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}