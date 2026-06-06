<?php

declare(strict_types=1);

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class SupplierBalance extends CoreModel
{
    protected $table = 'supplier_balances';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'opening_balance' => 'decimal:6',
            'invoice_total' => 'decimal:6',
            'payment_total' => 'decimal:6',
            'credit_total' => 'decimal:6',
            'debit_total' => 'decimal:6',
            'outstanding_balance' => 'decimal:6',
            'last_transaction_date' => 'date',
        ]);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
