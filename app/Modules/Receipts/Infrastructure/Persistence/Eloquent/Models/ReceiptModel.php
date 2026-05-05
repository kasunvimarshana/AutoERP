<?php

declare(strict_types=1);

namespace Modules\Receipts\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ReceiptModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_receipts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'org_unit_id',
        'row_version',
        'receipt_number',
        'payment_id',
        'invoice_id',
        'receipt_type',
        'status',
        'amount',
        'currency',
        'issued_at',
        'notes',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'string',
        'issued_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'row_version' => 'integer',
    ];
}
