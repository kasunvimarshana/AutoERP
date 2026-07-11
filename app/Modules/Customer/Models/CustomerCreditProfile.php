<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class CustomerCreditProfile extends TenantOwnedModel
{
    protected $table = 'customer_credit_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'credit_limit' => 'decimal:6',
            'credit_period_days' => 'integer',
            'warning_threshold_percent' => 'decimal:6',
            'credit_allowed' => 'boolean',
            'advance_allowed' => 'boolean',
            'allow_over_credit' => 'boolean',
            'allow_partial_payment' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
