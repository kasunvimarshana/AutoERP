<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Sales\Domain\Enums\ContactType;

/**
 * Customer / Supplier contact (from PHP_POS reference).
 */
class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contacts';

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'email',
        'phone',
        'tax_number',
        'opening_balance',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'is_active'       => 'boolean',
        'type'            => ContactType::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) {
                $q->where('contacts.tenant_id', app('tenant.id'));
            }
        });
    }

    public function sales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }
}
