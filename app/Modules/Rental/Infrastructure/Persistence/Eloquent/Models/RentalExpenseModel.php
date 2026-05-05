<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalExpenseModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'rental_expenses';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'rental_booking_id',
        'asset_id',
        'expense_type',
        'incurred_at',
        'supplier_id',
        'employee_id',
        'currency_id',
        'amount',
        'tax_amount',
        'total_amount',
        'status',
        'journal_entry_id',
        'payment_id',
        'reversal_of_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'rental_booking_id' => 'integer',
        'asset_id' => 'integer',
        'supplier_id' => 'integer',
        'employee_id' => 'integer',
        'currency_id' => 'integer',
        'journal_entry_id' => 'integer',
        'payment_id' => 'integer',
        'reversal_of_id' => 'integer',
        'amount' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'incurred_at' => 'datetime',
        'metadata' => 'array',
    ];
}
