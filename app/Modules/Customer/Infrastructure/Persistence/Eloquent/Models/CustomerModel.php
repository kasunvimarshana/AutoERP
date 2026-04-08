<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class CustomerModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'type',
        'name',
        'code',
        'email',
        'phone',
        'tax_number',
        'currency',
        'payment_terms',
        'credit_limit',
        'address',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:6',
        'address'      => 'array',
        'metadata'     => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
