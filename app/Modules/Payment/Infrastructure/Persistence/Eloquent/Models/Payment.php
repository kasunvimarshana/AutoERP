<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Payment\Domain\Enums\PaymentDirection;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePayment;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentAllocation;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentMethod;

class Payment extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'payment_date' => 'date',
            'amount' => 'decimal:4',
            'direction' => PaymentDirection::class,
            'exchange_rate' => 'decimal:4',
            'base_amount' => 'decimal:4',
            'status' => PaymentStatus::class,
        ];
    }

    #[Scope]
    protected function posted(Builder $query): void
    {
        $query->where('status', PaymentStatus::Posted->value);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'payment_id');
    }

    public function advancePayments(): HasMany
    {
        return $this->hasMany(AdvancePayment::class, 'payment_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'account_id'
        );
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\JournalEntry',
            'journal_entry_id'
        );
    }
}
