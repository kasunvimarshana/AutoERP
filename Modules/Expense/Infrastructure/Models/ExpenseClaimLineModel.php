<?php

namespace Modules\Expense\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ExpenseClaimLineModel extends Model
{
    use HasTenantScope;

    protected $table = 'expense_claim_lines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'claim_id',
        'expense_category_id',
        'description',
        'expense_date',
        'amount',
        'receipt_path',
    ];

    protected $casts = [
        'expense_date' => 'date',
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
