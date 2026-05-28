<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CustomerCreditProfileModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'customer_credit_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'credit_limit' => 'decimal:4',
            'credit_days' => 'integer',
            'credit_hold' => 'boolean',
            'allow_credit_override' => 'boolean',
            'credit_hold_by' => 'integer',
            'credit_hold_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
