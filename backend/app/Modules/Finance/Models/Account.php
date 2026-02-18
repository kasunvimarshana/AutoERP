<?php

namespace App\Modules\Finance\Models;

use App\Models\Currency;
use App\Models\Tenant;
use App\Modules\Finance\Enums\AccountType;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected static function newFactory()
    {
        return \Database\Factories\Finance\AccountFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'type',
        'parent_id',
        'currency_id',
        'opening_balance',
        'current_balance',
        'is_active',
        'description',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, AccountType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRootAccounts($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getFullCodeAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_code.'.'.$this->code;
        }

        return $this->code;
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_name.' > '.$this->name;
        }

        return $this->name;
    }
}
