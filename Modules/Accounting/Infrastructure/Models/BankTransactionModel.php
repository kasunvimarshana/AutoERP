<?php

namespace Modules\Accounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class BankTransactionModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'accounting_bank_transactions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'bank_account_id',
        'type',
        'amount',
        'transaction_date',
        'description',
        'status',
        'reference_number',
        'journal_entry_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'string',
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

    public function bankAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BankAccountModel::class, 'bank_account_id');
    }
}
