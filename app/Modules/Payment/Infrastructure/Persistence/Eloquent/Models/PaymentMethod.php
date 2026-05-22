<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Payment\Domain\Enums\PaymentMethodType;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payment;

class PaymentMethod extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'payment_methods';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => PaymentMethodType::class,
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_method_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'account_id'
        );
    }
}
