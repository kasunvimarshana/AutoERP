<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;
use Modules\Customer\Domain\Enums\CustomerStatus;
use Modules\Customer\Domain\Enums\CustomerType;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerAddress;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerContact;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerVehicle;

class Customer extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'customers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => CustomerType::class,
            'status' => CustomerStatus::class,
            'credit_limit' => 'decimal:4',
            'payment_terms_days' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', CustomerStatus::Active->value);
    }

    protected function registrationNumber(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value === null ? null : strtoupper(trim($value)),
        );
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class, 'customer_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicle::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'user_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'ar_account_id'
        );
    }
}
