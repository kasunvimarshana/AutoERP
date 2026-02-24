<?php

namespace Modules\Expense\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ExpenseClaimModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'expense_claims';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'employee_id',
        'title',
        'description',
        'currency',
        'total_amount',
        'status',
        'approver_id',
        'submitted_at',
        'approved_at',
        'reimbursed_at',
    ];

    protected $casts = [
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
        'reimbursed_at' => 'datetime',
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
