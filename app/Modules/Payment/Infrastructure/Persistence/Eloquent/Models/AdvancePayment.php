<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Payment\Domain\Enums\AdvancePaymentStatus;
use Modules\Payment\Domain\Enums\AdvancePaymentType;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentAllocation;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payment;

class AdvancePayment extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'advance_payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'amount' => 'decimal:4',
            'remaining_amount' => 'decimal:4',
            'advance_date' => 'date',
            'type' => AdvancePaymentType::class,
            'status' => AdvancePaymentStatus::class,
        ];
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', AdvancePaymentStatus::Open->value);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(AdvancePaymentAllocation::class, 'advance_payment_id');
    }
}
