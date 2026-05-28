<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierBankAccountModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'supplier_bank_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'currency_id' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
