<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'category_id' => 'integer',
            'default_currency_id' => 'integer',
            'default_payment_term_id' => 'integer',
            'default_payable_account_id' => 'integer',
            'default_expense_account_id' => 'integer',
            'credit_limit' => 'decimal:4',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'blocked_at' => 'datetime',
            'unblocked_at' => 'datetime',
            'archived_at' => 'datetime',
            'row_version' => 'integer',
        ]);
    }
}
