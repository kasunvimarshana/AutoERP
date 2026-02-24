<?php

namespace Modules\Accounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class InvoiceModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'accounting_invoices';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'number',
        'invoice_type',
        'source_invoice_id',
        'partner_id',
        'partner_type',
        'status',
        'subtotal',
        'tax_total',
        'total',
        'amount_paid',
        'amount_due',
        'currency',
        'due_date',
        'notes',
        'created_by',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
