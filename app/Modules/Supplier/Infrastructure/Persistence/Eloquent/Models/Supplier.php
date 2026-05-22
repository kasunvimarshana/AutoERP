<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;
use Modules\Supplier\Domain\Enums\SupplierStatus;
use Modules\Supplier\Domain\Enums\SupplierType;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddress;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContact;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierItem;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierVehicle;

class Supplier extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => SupplierType::class,
            'status' => SupplierStatus::class,
            'credit_limit' => 'decimal:4',
            'payment_terms_days' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', SupplierStatus::Active->value);
    }

    protected function registrationNumber(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value === null ? null : strtoupper(trim($value)),
        );
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class, 'supplier_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class, 'supplier_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(SupplierVehicle::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierItem::class, 'supplier_id');
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

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'ap_account_id'
        );
    }
}
