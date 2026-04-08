<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ReturnModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'returns';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'org_unit_id',
        'reference_number',
        'type',
        'original_order_id',
        'supplier_id',
        'customer_id',
        'warehouse_id',
        'status',
        'return_date',
        'reason',
        'subtotal',
        'tax_amount',
        'total_amount',
        'restock_location_id',
        'credit_memo_number',
        'credit_memo_issued_at',
        'fee_amount',
        'fee_description',
        'notes',
        'internal_notes',
        'metadata',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'subtotal'              => 'decimal:6',
        'tax_amount'            => 'decimal:6',
        'total_amount'          => 'decimal:6',
        'fee_amount'            => 'decimal:6',
        'metadata'              => 'array',
        'return_date'           => 'date',
        'approved_at'           => 'datetime',
        'processed_at'          => 'datetime',
        'credit_memo_issued_at' => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
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

    public function lines(): HasMany
    {
        return $this->hasMany(ReturnLineModel::class, 'return_id');
    }
}
