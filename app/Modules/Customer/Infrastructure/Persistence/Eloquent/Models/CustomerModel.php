<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CustomerModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'customers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'category_id' => 'integer',
            'default_currency_id' => 'integer',
            'default_payment_term_id' => 'integer',
            'default_receivable_account_id' => 'integer',
            'default_income_account_id' => 'integer',
            'credit_limit' => 'decimal:4',
            'payment_terms_days' => 'integer',
            'credit_hold' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(CustomerAddressModel::class, 'customer_id')
            ->where('is_primary', true);
    }
}
