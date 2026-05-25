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
            'user_id' => 'integer',
            'currency_id' => 'integer',
            'credit_limit' => 'decimal:4',
            'payment_terms_days' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'ar_account_id' => 'integer',
        ]);
    }
}