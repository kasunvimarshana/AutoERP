<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Voucher\Domain\Enums\RecurringVoucherFrequency;
use Modules\Voucher\Domain\Enums\VoucherType;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class RecurringVoucher extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'recurring_vouchers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => VoucherType::class,
            'tax_rate' => 'decimal:4',
            'amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'frequency' => RecurringVoucherFrequency::class,
            'interval' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_run_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'account_id'
        );
    }

    public function contraAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'contra_account_id'
        );
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\TaxRate',
            'tax_rate_id'
        );
    }

    public function party(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'party_type', 'party_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User',
            'created_by'
        );
    }
}
