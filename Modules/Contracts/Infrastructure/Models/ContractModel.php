<?php

namespace Modules\Contracts\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ContractModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'contracts';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'title',
        'description',
        'type',
        'party_name',
        'party_email',
        'party_reference',
        'start_date',
        'end_date',
        'total_value',
        'currency',
        'payment_terms',
        'notes',
        'status',
        'activated_at',
        'terminated_at',
        'termination_reason',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'activated_at'  => 'datetime',
        'terminated_at' => 'datetime',
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
