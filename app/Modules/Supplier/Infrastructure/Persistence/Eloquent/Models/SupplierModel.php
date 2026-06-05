<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'category_id' => 'integer',
            'default_currency_id' => 'integer',
            'default_payment_term_id' => 'integer',
            'default_payable_account_id' => 'integer',
            'default_expense_account_id' => 'integer',
            'credit_limit' => 'decimal:4',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(SupplierAddressModel::class, 'supplier_id')
            ->where('is_default', true);
    }
}
