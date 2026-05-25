<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CashRegisterModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'cash_registers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'cash_account_id' => 'integer',
            'opening_balance' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'is_active' => 'boolean'
        ]);
    }
}