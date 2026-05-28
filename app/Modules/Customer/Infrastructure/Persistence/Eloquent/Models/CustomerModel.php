<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CustomerModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'customers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'category_id' => 'integer',
            'default_currency_id' => 'integer',
            'default_payment_term_id' => 'integer',
            'default_receivable_account_id' => 'integer',
            'default_income_account_id' => 'integer',
            'credit_limit' => 'decimal:4',
            'credit_days' => 'integer',
            'credit_hold' => 'boolean',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'activated_by' => 'integer',
            'deactivated_by' => 'integer',
            'blocked_by' => 'integer',
            'unblocked_by' => 'integer',
            'archived_by' => 'integer',
            'credit_hold_by' => 'integer',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'blocked_at' => 'datetime',
            'unblocked_at' => 'datetime',
            'archived_at' => 'datetime',
            'credit_hold_at' => 'datetime',
        ]);
    }
}