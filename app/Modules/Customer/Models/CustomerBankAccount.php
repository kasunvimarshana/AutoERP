<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;

final class CustomerBankAccount extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'customer_bank_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'currency_id' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }
}
