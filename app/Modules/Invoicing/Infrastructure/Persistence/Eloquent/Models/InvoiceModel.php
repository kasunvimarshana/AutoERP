<?php

declare(strict_types=1);

namespace Modules\Invoicing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class InvoiceModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_invoices';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'org_unit_id',
        'row_version',
        'invoice_number',
        'invoice_type',
        'entity_type',
        'entity_id',
        'status',
        'issue_date',
        'due_date',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'currency',
        'notes',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal_amount' => 'string',
        'tax_amount' => 'string',
        'total_amount' => 'string',
        'paid_amount' => 'string',
        'balance_amount' => 'string',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'row_version' => 'integer',
    ];
}
